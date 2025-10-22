# SendGrid Setup Instructions (FREE - 100 emails/day)

If GoDaddy mail() doesn't work, SendGrid is the BEST solution:
- ✅ Free tier: 100 emails/day forever
- ✅ No SMTP ports needed (uses HTTPS API)
- ✅ Works on ALL hosting providers
- ✅ Better deliverability than mail()
- ✅ Email tracking and analytics

## Step 1: Create SendGrid Account

1. Go to: https://signup.sendgrid.com/
2. Sign up for FREE account
3. Verify your email address
4. Complete the "Get Started" checklist

## Step 2: Get API Key

1. In SendGrid dashboard, go to: **Settings > API Keys**
2. Click **"Create API Key"**
3. Name it: `Toy Appeal`
4. Choose: **"Full Access"** (or at minimum "Mail Send")
5. Click **"Create & View"**
6. **COPY THE API KEY** (you won't see it again!)

Example API key format: `SG.xxxxxxxxxxxxxxxxxx.yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy`

## Step 3: Verify Sender Email

1. Go to: **Settings > Sender Authentication > Single Sender Verification**
2. Click **"Create New Sender"**
3. Enter:
   - From Name: `Alive Church Christmas Toy Appeal`
   - From Email: `office@alive.me.uk`
   - Reply To: `office@alive.me.uk`
   - Address: Your church address
4. SendGrid will email office@alive.me.uk with a verification link
5. Click the link to verify

## Step 4: Install SendGrid PHP Library

On your server (via SSH or cPanel Terminal):

```bash
cd /path/to/refsys
composer require sendgrid/sendgrid
```

Or if composer isn't available, I'll provide a standalone version.

## Step 5: Update Database Settings

Run this in phpMyAdmin:

```sql
-- Set email method to SendGrid
UPDATE settings SET setting_value = 'sendgrid' WHERE setting_key = 'email_method';

-- Add SendGrid API key (replace with YOUR key)
INSERT INTO settings (setting_key, setting_value)
VALUES ('sendgrid_api_key', 'SG.your_api_key_here')
ON DUPLICATE KEY UPDATE setting_value = 'SG.your_api_key_here';
```

## Step 6: Test

Visit: `https://your-domain.com/test_sendgrid.php`

---

## Alternative: AWS SES Sandbox Mode

AWS SES has a "sandbox" mode that doesn't require production approval:
- You can send emails to verified addresses only
- Free tier: 62,000 emails/month
- Perfect for testing and small deployments

Would you like me to set this up instead?

---

## Which Should You Choose?

| Solution | Pros | Cons | Best For |
|----------|------|------|----------|
| **GoDaddy mail()** | Simple, no setup | Often disabled, poor deliverability | Small deployments where it works |
| **SendGrid** | Easy setup, reliable, free 100/day | Requires account signup | Most situations (RECOMMENDED) |
| **AWS SES Sandbox** | Very cheap, scalable | Only verified emails, more complex | If you already use AWS |
| **Mailgun** | Similar to SendGrid | Paid after 3 months | Commercial projects |

**RECOMMENDATION: Use SendGrid - it's the most reliable and easiest.**
