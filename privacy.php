<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = "Privacy Policy";
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-screen py-8 px-4">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Privacy Policy</h1>
            <p class="text-sm text-gray-600">Last updated: <?php echo date('F Y'); ?></p>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-8 space-y-6">
            <!-- Introduction -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">Introduction</h2>
                <p class="text-gray-700">
                    Alive Church is committed to protecting your privacy and handling your personal data in accordance with
                    the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018. This privacy policy
                    explains how we collect, use, and protect your information when you use our Christmas Toy Appeal referral system.
                </p>
            </section>

            <!-- Data Controller -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">Data Controller</h2>
                <p class="text-gray-700 mb-2">Alive Church is the data controller for the information you provide. You can contact us at:</p>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-gray-700"><strong>Alive Church</strong></p>
                    <p class="text-gray-700">Alive House</p>
                    <p class="text-gray-700">Nelson Street</p>
                    <p class="text-gray-700">Norwich, NR2 4DR</p>
                    <p class="text-gray-700">Email: <a href="mailto:office@alive.me.uk" class="text-[#eb008b] hover:text-[#c00074] underline">office@alive.me.uk</a></p>
                </div>
            </section>

            <!-- What Data We Collect -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">What Data We Collect</h2>
                <p class="text-gray-700 mb-3">When you submit a referral through our system, we collect:</p>
                <ul class="list-disc pl-6 text-gray-700 space-y-2">
                    <li><strong>Referrer Information:</strong> Your name, organisation name, team name, phone number, email address, and secondary contact</li>
                    <li><strong>Family Information:</strong> Family postcode, duration the family has been known to your organisation, and any additional notes</li>
                    <li><strong>Children's Information:</strong> Child initials (not full names), age, gender, and any special requirements</li>
                    <li><strong>Consent Record:</strong> Date and time of your consent to data processing</li>
                </ul>
            </section>

            <!-- How We Use Your Data -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">How We Use Your Data</h2>
                <p class="text-gray-700 mb-3">We use your personal data for the following purposes:</p>
                <ul class="list-disc pl-6 text-gray-700 space-y-2">
                    <li>Processing toy appeal referrals for families in need</li>
                    <li>Matching children with appropriate toys based on age, gender, and requirements</li>
                    <li>Contacting you to confirm receipt of referrals</li>
                    <li>Notifying you when toy parcels are ready for collection</li>
                    <li>Managing collection logistics</li>
                    <li>Maintaining records for safeguarding and accountability purposes</li>
                </ul>
                <p class="text-gray-700 mt-3">
                    <strong>Legal Basis:</strong> We process your data based on your explicit consent, which you provide
                    when submitting a referral form. You have the right to withdraw this consent at any time.
                </p>
            </section>

            <!-- Data Retention -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">Data Retention</h2>
                <p class="text-gray-700">
                    We retain referral data for one year following the completion of the Christmas Toy Appeal season.
                    After this period, all personal data is securely deleted unless there is a legitimate reason to
                    retain it (e.g., safeguarding concerns or legal requirements).
                </p>
            </section>

            <!-- Data Sharing -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">Who We Share Your Data With</h2>
                <p class="text-gray-700 mb-3">We do not sell or share your personal data with third parties, except:</p>
                <ul class="list-disc pl-6 text-gray-700 space-y-2">
                    <li><strong>Authorized Volunteers:</strong> Alive Church staff and volunteers involved in fulfilling and managing toy appeal referrals</li>
                    <li><strong>Email Service Provider:</strong> Amazon SES (Simple Email Service) for sending confirmation and collection emails</li>
                    <li><strong>Hosting Provider:</strong> GoDaddy for secure data storage</li>
                    <li><strong>Legal Requirements:</strong> If required by law or to protect the safety of children</li>
                </ul>
                <p class="text-gray-700 mt-3">
                    All third-party providers are required to maintain appropriate security measures and only process
                    data in accordance with our instructions.
                </p>
            </section>

            <!-- Data Security -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">Data Security</h2>
                <p class="text-gray-700">
                    We take appropriate technical and organizational measures to protect your personal data against
                    unauthorized access, loss, or misuse. This includes:
                </p>
                <ul class="list-disc pl-6 text-gray-700 space-y-2 mt-2">
                    <li>Secure HTTPS encryption for all data transmission</li>
                    <li>Restricted access to personal data (authorized users only)</li>
                    <li>Regular security updates and monitoring</li>
                    <li>Password-protected admin areas</li>
                </ul>
            </section>

            <!-- Your Rights -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">Your Rights Under UK GDPR</h2>
                <p class="text-gray-700 mb-3">You have the following rights regarding your personal data:</p>
                <ul class="list-disc pl-6 text-gray-700 space-y-2">
                    <li><strong>Right of Access:</strong> Request a copy of the personal data we hold about you</li>
                    <li><strong>Right to Rectification:</strong> Request correction of inaccurate or incomplete data</li>
                    <li><strong>Right to Erasure:</strong> Request deletion of your personal data (subject to certain conditions)</li>
                    <li><strong>Right to Restrict Processing:</strong> Request that we limit how we use your data</li>
                    <li><strong>Right to Data Portability:</strong> Request a copy of your data in a structured format</li>
                    <li><strong>Right to Object:</strong> Object to processing of your data in certain circumstances</li>
                    <li><strong>Right to Withdraw Consent:</strong> Withdraw your consent to data processing at any time</li>
                </ul>
                <p class="text-gray-700 mt-3">
                    To exercise any of these rights, please contact us at
                    <a href="mailto:office@alive.me.uk" class="text-[#eb008b] hover:text-[#c00074] underline">office@alive.me.uk</a>.
                    We will respond to your request within one month.
                </p>
            </section>

            <!-- Complaints -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">Right to Complain</h2>
                <p class="text-gray-700">
                    If you are unhappy with how we have handled your personal data, you have the right to lodge a complaint
                    with the Information Commissioner's Office (ICO):
                </p>
                <div class="bg-gray-50 p-4 rounded-lg mt-3">
                    <p class="text-gray-700"><strong>Information Commissioner's Office</strong></p>
                    <p class="text-gray-700">Wycliffe House, Water Lane</p>
                    <p class="text-gray-700">Wilmslow, Cheshire SK9 5AF</p>
                    <p class="text-gray-700">Phone: 0303 123 1113</p>
                    <p class="text-gray-700">Website: <a href="https://ico.org.uk" target="_blank" class="text-[#eb008b] hover:text-[#c00074] underline">www.ico.org.uk</a></p>
                </div>
            </section>

            <!-- Cookies -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">Cookies and Tracking</h2>
                <p class="text-gray-700">
                    Our website uses minimal cookies necessary for the website to function (session cookies for login).
                    We do not use tracking cookies or analytics. Session cookies are automatically deleted when you close your browser.
                </p>
            </section>

            <!-- Changes to Policy -->
            <section>
                <h2 class="text-2xl font-semibold text-gray-900 mb-3">Changes to This Policy</h2>
                <p class="text-gray-700">
                    We may update this privacy policy from time to time. Any changes will be posted on this page with an
                    updated "Last Updated" date. We encourage you to review this policy periodically.
                </p>
            </section>

            <!-- Contact -->
            <section class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Questions?</h3>
                <p class="text-gray-700">
                    If you have any questions about this privacy policy or how we handle your data, please contact us at
                    <a href="mailto:office@alive.me.uk" class="text-[#eb008b] hover:text-[#c00074] underline">office@alive.me.uk</a>
                </p>
            </section>

            <!-- Back Link -->
            <div class="text-center pt-6 border-t">
                <a href="index.php" class="text-[#eb008b] hover:text-[#c00074] font-medium">
                    &larr; Back to Referral Form
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
