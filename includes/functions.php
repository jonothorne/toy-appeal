<?php
require_once __DIR__ . '/db.php';

// Generate unique reference number
function generateReferenceNumber() {
    $year = getSetting('current_year', date('Y'));
    $prefix = "TOY-{$year}-";

    // Get the last reference number for this year
    $lastRef = getRow(
        "SELECT reference_number FROM referrals WHERE reference_number LIKE ? ORDER BY id DESC LIMIT 1",
        "s",
        [$prefix . '%']
    );

    if ($lastRef) {
        $lastNumber = intval(substr($lastRef['reference_number'], -4));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }

    return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

// Create household and referrals
function createReferral($referrerData, $childrenData) {
    // Start transaction
    $conn = getDBConnection();
    $conn->begin_transaction();

    try {
        // Insert household
        $householdId = insertQuery(
            "INSERT INTO households (referrer_name, referrer_organisation, referrer_team, secondary_contact, referrer_phone, referrer_email, postcode, duration_known, additional_notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "sssssssss",
            [
                $referrerData['name'],
                $referrerData['organisation'],
                $referrerData['team'] ?? '',
                $referrerData['secondary_contact'] ?? '',
                $referrerData['phone'],
                $referrerData['email'],
                $referrerData['postcode'],
                $referrerData['duration_known'],
                $referrerData['additional_notes'] ?? ''
            ]
        );

        if (!$householdId) {
            throw new Exception("Failed to create household");
        }

        // Insert each child as a separate referral
        $referralIds = [];
        foreach ($childrenData as $child) {
            $referenceNumber = generateReferenceNumber();

            $referralId = insertQuery(
                "INSERT INTO referrals (reference_number, household_id, child_initials, child_age, child_gender, special_requirements)
                 VALUES (?, ?, ?, ?, ?, ?)",
                "sissss",
                [
                    $referenceNumber,
                    $householdId,
                    $child['initials'],
                    $child['age'],
                    $child['gender'],
                    $child['special_requirements'] ?? ''
                ]
            );

            if (!$referralId) {
                throw new Exception("Failed to create referral for child");
            }

            $referralIds[] = $referralId;
        }

        // Commit transaction
        $conn->commit();

        return [
            'success' => true,
            'household_id' => $householdId,
            'referral_ids' => $referralIds
        ];

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Create referral error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Update referral status
function updateReferralStatus($referralId, $newStatus, $userId = null, $zoneId = null, $zoneProvided = false) {
    $conn = getDBConnection();

    // Get current status
    $currentReferral = getRow("SELECT status, zone_id FROM referrals WHERE id = ?", "i", [$referralId]);

    if (!$currentReferral) {
        return false;
    }

    // Build update query based on status
    $timestampField = null;
    $userField = null;

    switch ($newStatus) {
        case 'fulfilled':
            $timestampField = 'fulfilled_at';
            $userField = 'fulfilled_by';
            break;
        case 'located':
            $timestampField = 'located_at';
            break;
        case 'ready_for_collection':
            $timestampField = 'ready_at';
            break;
        case 'collected':
            $timestampField = 'collected_at';
            $userField = 'collected_by';
            break;
    }

    // Build SQL
    $updates = ["status = ?"];
    $types = "s";
    $params = [$newStatus];

    if ($timestampField) {
        $updates[] = "{$timestampField} = NOW()";
    }

    if ($userField && $userId) {
        $updates[] = "{$userField} = ?";
        $types .= "i";
        $params[] = $userId;
    }

    // Always update zone if provided (not just when status is 'located')
    if ($zoneProvided) {
        if ($zoneId === null) {
            $updates[] = "zone_id = NULL";
        } else {
            $updates[] = "zone_id = ?";
            $types .= "i";
            $params[] = $zoneId;
        }
    }

    $types .= "i";
    $params[] = $referralId;

    $sql = "UPDATE referrals SET " . implode(", ", $updates) . " WHERE id = ?";
    $result = updateQuery($sql, $types, $params);

    if ($result !== false) {
        // Log activity for status change
        if ($currentReferral['status'] != $newStatus) {
            logActivity($referralId, $userId, "Status changed", $currentReferral['status'], $newStatus);
        }

        // Log activity for zone change
        if ($zoneProvided && $currentReferral['zone_id'] != $zoneId) {
            $oldZone = $currentReferral['zone_id'] ? "Zone " . $currentReferral['zone_id'] : "None";
            $newZone = $zoneId ? "Zone " . $zoneId : "None";
            logActivity($referralId, $userId, "Zone changed", $oldZone, $newZone);
        }

        // Send email if status is ready_for_collection AND all siblings are ready
        if ($newStatus === 'ready_for_collection') {
            // Get the household_id for this referral
            $referral = getRow("SELECT household_id FROM referrals WHERE id = ?", "i", [$referralId]);

            if ($referral) {
                // Check if ALL children in this household are now ready for collection
                $allReady = checkIfAllHouseholdChildrenReady($referral['household_id']);

                if ($allReady) {
                    // All children are ready - send the email!
                    sendCollectionReadyEmail($referralId);
                }
                // If not all ready yet, don't send email - wait for the last child
            }
        }
    }

    return $result !== false;
}

// Check if all children in a household are ready for collection
function checkIfAllHouseholdChildrenReady($householdId) {
    // Get total number of children in this household
    $totalChildren = getRow(
        "SELECT COUNT(*) as total FROM referrals WHERE household_id = ?",
        "i",
        [$householdId]
    );

    // Get number of children ready for collection (or already collected)
    $readyChildren = getRow(
        "SELECT COUNT(*) as total FROM referrals
         WHERE household_id = ?
         AND status IN ('ready_for_collection', 'collected')",
        "i",
        [$householdId]
    );

    // All children are ready if the counts match
    return ($totalChildren['total'] === $readyChildren['total']);
}

// Log activity
function logActivity($referralId, $userId, $action, $oldValue = null, $newValue = null) {
    return insertQuery(
        "INSERT INTO activity_log (referral_id, user_id, action, old_value, new_value) VALUES (?, ?, ?, ?, ?)",
        "iisss",
        [$referralId, $userId, $action, $oldValue, $newValue]
    );
}

// Get referral with household info
function getReferralWithHousehold($referralId) {
    $sql = "SELECT r.*, h.*, z.zone_name, z.location as zone_location, z.description as zone_description,
                   r.id as referral_id,
                   r.created_at as referral_created,
                   u1.full_name as fulfilled_by_name,
                   u2.full_name as collected_by_name
            FROM referrals r
            JOIN households h ON r.household_id = h.id
            LEFT JOIN zones z ON r.zone_id = z.id
            LEFT JOIN users u1 ON r.fulfilled_by = u1.id
            LEFT JOIN users u2 ON r.collected_by = u2.id
            WHERE r.id = ?";

    return getRow($sql, "i", [$referralId]);
}

// Get siblings from same household
function getSiblings($householdId, $excludeReferralId = null) {
    if ($excludeReferralId) {
        return getRows(
            "SELECT * FROM referrals WHERE household_id = ? AND id != ? ORDER BY child_age DESC",
            "ii",
            [$householdId, $excludeReferralId]
        );
    } else {
        return getRows(
            "SELECT * FROM referrals WHERE household_id = ? ORDER BY child_age DESC",
            "i",
            [$householdId]
        );
    }
}

// Search referrals
function searchReferrals($search = '', $status = '', $zone = '', $page = 1, $perPage = 20) {
    $offset = ($page - 1) * $perPage;

    // Build WHERE clause
    $where = [];
    $types = "";
    $params = [];

    if (!empty($search)) {
        $where[] = "(r.reference_number LIKE ? OR r.child_initials LIKE ? OR h.referrer_name LIKE ? OR h.referrer_organisation LIKE ? OR h.postcode LIKE ?)";
        $searchTerm = "%{$search}%";
        $types .= "sssss";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }

    if (!empty($status)) {
        $where[] = "r.status = ?";
        $types .= "s";
        $params[] = $status;
    }

    if (!empty($zone)) {
        if ($zone === 'unassigned') {
            $where[] = "r.zone_id IS NULL";
        } else {
            $where[] = "r.zone_id = ?";
            $types .= "i";
            $params[] = intval($zone);
        }
    }

    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM referrals r JOIN households h ON r.household_id = h.id {$whereClause}";
    $countResult = getRow($countSql, $types, $params);
    $total = $countResult['total'];

    // Get results
    $sql = "SELECT r.*, h.referrer_name, h.referrer_organisation, h.postcode, z.zone_name
            FROM referrals r
            JOIN households h ON r.household_id = h.id
            LEFT JOIN zones z ON r.zone_id = z.id
            {$whereClause}
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?";

    $types .= "ii";
    $params[] = $perPage;
    $params[] = $offset;

    $results = getRows($sql, $types, $params);

    return [
        'results' => $results,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($total / $perPage)
    ];
}

// Get statistics
function getStatistics() {
    $stats = [];

    // Total referrals
    $result = getRow("SELECT COUNT(*) as count FROM referrals");
    $stats['total_referrals'] = $result['count'];

    // Total households
    $result = getRow("SELECT COUNT(*) as count FROM households");
    $stats['total_households'] = $result['count'];

    // By status
    $statusCounts = getRows("SELECT status, COUNT(*) as count FROM referrals GROUP BY status");
    foreach ($statusCounts as $row) {
        $stats['by_status'][$row['status']] = $row['count'];
    }

    // By organisation
    $stats['by_organisation'] = getRows(
        "SELECT h.referrer_organisation, COUNT(r.id) as count
         FROM households h
         JOIN referrals r ON h.id = r.household_id
         GROUP BY h.referrer_organisation
         ORDER BY count DESC
         LIMIT 10"
    );

    // By postcode area (first part)
    $stats['by_postcode'] = getRows(
        "SELECT SUBSTRING_INDEX(postcode, ' ', 1) as postcode_area, COUNT(r.id) as count
         FROM households h
         JOIN referrals r ON h.id = r.household_id
         GROUP BY postcode_area
         ORDER BY count DESC
         LIMIT 10"
    );

    // By age group
    $stats['by_age_group'] = getRows(
        "SELECT
            CASE
                WHEN child_age <= 2 THEN '0-2'
                WHEN child_age <= 5 THEN '3-5'
                WHEN child_age <= 8 THEN '6-8'
                WHEN child_age <= 12 THEN '9-12'
                ELSE '13+'
            END as age_group,
            COUNT(*) as count
         FROM referrals
         GROUP BY age_group
         ORDER BY age_group"
    );

    // By gender
    $stats['by_gender'] = getRows("SELECT child_gender, COUNT(*) as count FROM referrals GROUP BY child_gender");

    // By duration known
    $stats['by_duration'] = getRows(
        "SELECT h.duration_known, COUNT(r.id) as count
         FROM households h
         JOIN referrals r ON h.id = r.household_id
         GROUP BY h.duration_known
         ORDER BY FIELD(h.duration_known, '<1 month', '1-6 months', '6-12 months', '1-2 years', '2+ years')"
    );

    // Recent activity
    $stats['recent_referrals'] = getRows(
        "SELECT COUNT(*) as count FROM referrals WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    )[0]['count'];

    $stats['ready_for_collection'] = getRow(
        "SELECT COUNT(*) as count FROM referrals WHERE status = 'ready_for_collection'"
    )['count'];

    return $stats;
}

// Sanitize output
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Format date
function formatDate($date, $format = 'd/m/Y H:i') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

// Get status badge HTML
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-800">Pending</span>',
        'fulfilled' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-200 text-blue-800">Fulfilled</span>',
        'located' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-200 text-purple-800">Located</span>',
        'ready_for_collection' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-200 text-yellow-800">Ready</span>',
        'collected' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-200 text-green-800">Collected</span>'
    ];

    return $badges[$status] ?? $status;
}
