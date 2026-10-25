# Getting Started

How to sign in, stay signed in, reset a password or register, and what each role can do.

Press `?` on this screen for a short summary.

**Where:** **Sign In** in the top right of the header, or the sign-in page you are sent to when you open a staff page while signed out.

## Tasks

### Sign in

1. Enter **Your email** and **Password**.
2. Click **Sign in**.

You land on the page you asked for, or the home page. Open **Dashboard** from the menu for today's figures ([Reports and Dashboard](13-reports.md)).

On the playground, **Explore the Platform** signs you in to a shared demo account without a password and opens the **Dashboard**. Playground data and accounts reset every night.

### Sign out

Open the user menu (your avatar, top right) and click **Sign Out**.

### Reset a forgotten password

1. On the sign-in page, click **Forgot password?**
2. Enter your email and click **Send Reset Link**.
3. Open the email and follow the link within 1 hour.
4. Enter **New password** and **Confirm Password** (at least 6 characters), then click **Reset password**.
5. Sign in with the new password.

The **Check Your Email** page looks the same whether or not the address has an account, so it never reveals who has one.

### Register an account

1. On the sign-in page, click **Sign up**.
2. Enter **Full name**, **Email**, **Password** and **Confirm password** (at least 6 characters).
3. Tick **I accept the Terms and Conditions**, complete the security check, and click **Create account**.

You are signed in straight away and sent a verification email. A new account has no staff access: a staff member must tick **Member of Staff** on your customer record ([Customers](07-customers.md)).

## Rules and limits

- **Staying signed in:** every sign-in is remembered for 7 days. Each time the app signs you back in, the 7 days start again, so you are signed out only after 7 days away or when you click **Sign Out**.
- **Login throttling:** after 5 failed attempts for the same email in 15 minutes, sign-in is refused with "Too many failed login attempts, please try again in N minutes." Wait, or reset the password.
- **Sign-in page limit:** more than 10 requests a minute to the sign-in page from one connection shows a "Too Many Requests" page (error 429). Page loads and sign-in attempts both count.
- **Reset emails:** one per account per hour. A second request within the hour shows the usual page but sends no email; use the first email.
- **Sign-up and reset submissions:** from one connection, a burst of 3 submissions of the sign-up or password reset forms, then 1 every 30 seconds. Over that, a "Too Many Requests" page (error 429) is shown; wait a minute.
- **Registration:** at most 5 submitted sign-ups from one connection per hour, counting ones that failed validation ("Too many attempts. Please try again later.").
- **Email verification is not enforced.** An unverified account can sign in; verification only sets the **Verified** badge on the customer record. The page for requesting a new verification email (`/verify/resend`) is not linked from any screen.

## Roles

| Role | Shown as | Can do |
|------|----------|--------|
| User | A customer with no staff access | Sign in, and open only the public pages (home, about, contact, privacy, terms, help). Every staff page shows "Unauthorized Access to Mars Colony Systems". |
| Staff | **Member of Staff** ticked on the customer record | Everything in this manual, except the super admin actions below. |
| Super admin | Not shown or set on any screen | Everything staff can do, plus: create orders by hand (the **Create Order** button), delete products, suppliers, supplier products, VAT rates and pools, and edit staff accounts. |

Staff who try a super admin delete see "Deleting is disabled for this user." (in every environment, not only the playground). **Create Order**, and the edit controls on a staff account, are hidden from them.

Editing a staff account is super admin only, your own included, and that covers the full name too. A staff user who opens **Settings** sees no edit button and cannot click the name to change it.

The **Help** button and the `?` key work for everyone, signed in or not.

## Related

- [Navigation](02-navigation.md): the header, menu and common screens.
- [Customers](07-customers.md): granting staff access.
- [Troubleshooting](troubleshooting.md#signing-in): sign-in problems.
