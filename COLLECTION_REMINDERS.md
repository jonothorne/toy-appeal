# Collection Reminder System

Automatic email reminders for uncollected parcels.

## Features

- **Automatic Reminders**: Send emails to referrers when parcels haven't been collected after a configurable number of days
- **Configurable**: Enable/disable reminders and set the number of days before sending
- **QR Codes Included**: Reminder emails include QR codes for quick collection
- **One Reminder Per Parcel**: Each parcel only receives one reminder to avoid spam
- **Household Grouping**: One email per household, listing all uncollected parcels
- **Days Waiting Display**: Shows how many days parcels have been waiting

## Setup

### 1. Database Update

Run the SQL migration to add the required database field:

```bash
mysql -u your_username -p toyappeal_production < add_collection_reminders.sql
```

Or import `add_collection_reminders.sql` via phpMyAdmin.

This adds:
- `collection_reminder_sent_at` column to `referrals` table
- Settings for enabling/disabling reminders
- Settings for configuring reminder days

### 2. Configure Settings

Go to **Admin > Settings > General** and configure:

- **Enable Collection Reminders**: Check to enable the feature
- **Send Reminder After (Days)**: Number of days to wait before sending reminder (default: 3)

### 3. Set Up Cron Job

For automatic daily reminders, add this cron job to your server:

```bash
0 9 * * * cd /path/to/refsys && php cron_send_collection_reminders.php >> logs/collection_reminders.log 2>&1
```

This runs daily at 9:00 AM.

**To set up cron job on GoDaddy cPanel:**
1. Login to cPanel
2. Go to "Cron Jobs"
3. Add new cron job:
   - **Minute**: 0
   - **Hour**: 9
   - **Day**: *
   - **Month**: *
   - **Weekday**: *
   - **Command**: `cd /home/username/public_html/toyappeal && php cron_send_collection_reminders.php`

## Testing

### Test via Browser

Visit `test_collection_reminders.php` in your browser to:
- Check current settings
- See which parcels need reminders
- Send test reminders manually
- View parcels that have already received reminders

### Test via Command Line

Run the cron script manually to test:

```bash
php cron_send_collection_reminders.php
```

This will output progress and send actual emails.

## How It Works

### When Reminders Are Sent

The system checks for parcels that meet ALL these criteria:
1. Status is `ready_for_collection`
2. Created at least X days ago (X = configured reminder days)
3. No reminder has been sent yet (`collection_reminder_sent_at` IS NULL)

### Email Content

Reminder emails include:
- Friendly reminder message
- Days waiting count (e.g., "Waiting for 5 days")
- Collection location and hours
- List of all uncollected parcels for that household
- QR codes for each parcel for quick collection
- Instructions for what to do if already collected

### Preventing Duplicate Reminders

Once a reminder is sent:
- The `collection_reminder_sent_at` field is set to the current timestamp
- That parcel will not receive another reminder
- Only one reminder per parcel, regardless of how long it remains uncollected

### Household Grouping

If a family has multiple uncollected parcels:
- One email is sent per household
- All uncollected parcels are listed in the same email
- All QR codes are included in one email

## Email Example

**Subject**: Reminder: 2 Parcels Waiting for Collection

**Content**:
- Header: "⏰ Collection Reminder"
- Urgent box: "⏱ Waiting for 5 days"
- Collection details (location, hours)
- List of parcels ready to collect
- QR codes for each parcel
- Instructions and contact information

## Monitoring

### View Reminder History

In `test_collection_reminders.php`, you can see:
- Parcels that need reminders right now
- Parcels that will need reminders soon
- Parcels that have already received reminders
- When each reminder was sent

### Cron Job Logs

If you set up logging in your cron job:
```bash
tail -f logs/collection_reminders.log
```

Or check PHP error log for email-related issues.

## Troubleshooting

### Reminders Not Sending

1. **Check Settings**: Go to Admin > Settings > General
   - Is "Enable Collection Reminders" checked?
   - Is the days value configured correctly?

2. **Check Cron Job**:
   - Is the cron job set up correctly?
   - Is it running? Check cPanel > Cron Jobs
   - Check the logs for errors

3. **Check Email Settings**:
   - Is SendGrid configured correctly?
   - Check Admin > Settings > Email
   - Test with `test_collection_email.php`

4. **Manual Test**:
   - Visit `test_collection_reminders.php`
   - Click "Send Test Reminders Now"
   - Check for error messages

### No Parcels Showing Up

If no parcels appear in the test page:
- Check that parcels are in "ready_for_collection" status
- Check that parcels are old enough (e.g., 3+ days if set to 3 days)
- Check that reminders haven't already been sent

### Reminders Sent But Not Received

- Check spam/junk folders
- Verify SendGrid API key is correct
- Check SendGrid dashboard for delivery status
- Run `test_collection_email.php` to test email sending

## Customization

### Change Reminder Frequency

To change how many days before sending:
1. Go to Admin > Settings > General
2. Change "Send Reminder After (Days)"
3. Click "Save Changes"

### Change Reminder Time

Edit the cron job to run at a different time:
```bash
0 14 * * * ...  # Run at 2:00 PM instead
```

### Disable Reminders Temporarily

1. Go to Admin > Settings > General
2. Uncheck "Enable Collection Reminders"
3. Click "Save Changes"

The cron job will exit immediately if reminders are disabled.

## Files

- `add_collection_reminders.sql` - Database migration
- `cron_send_collection_reminders.php` - Automated cron job script
- `test_collection_reminders.php` - Manual testing interface
- `includes/email.php` - Contains `sendCollectionReminderEmail()` function
- `admin/settings.php` - Settings interface

## Support

For issues or questions:
- Check the test page: `test_collection_reminders.php`
- Review PHP error logs
- Check SendGrid dashboard for email delivery status
- Contact: office@alive.me.uk
