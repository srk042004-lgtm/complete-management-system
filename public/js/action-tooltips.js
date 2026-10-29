/*
 * Action tooltips for every button in the hotel and super admin dashboards.
 *
 * A tooltip is drawn once on <body> and positioned with fixed coordinates, so it is never clipped
 * by table wrappers or overflow containers. Each tip comes from, in order:
 *   1. data-tip="..." on the element, if present;
 *   2. the description list below, matched against the button's visible text;
 *   3. the button's aria-label, title, or visible text, so no button is left without a tooltip.
 * Sidebar and page navigation links are not buttons and are left alone.
 */
(function () {
    var DESCRIPTIONS = {
        // Housekeeping board
        'Start cleaning': 'Mark this room as being cleaned now.',
        'Finished · request inspection': 'Cleaning is done. Sends the room to a supervisor for inspection.',
        'Pass or fail inspection': 'Open the inspection checklist and pass the room or return it for touch-up.',
        'Pass inspection': 'Mark the room inspected and available to sell.',
        'Return to housekeeping': 'Send the room back for another clean, with a note explaining what to fix.',
        'Needs touch-up': 'The room is clean but needs a quick fix before it can be sold.',
        'Do not disturb': 'Keep housekeeping out while the guest is in the room.',
        'Log amenities': 'Record towels, toiletries and minibar items used or restocked. Minibar items are charged to the guest folio.',
        'Log lost & found': 'Record an item left behind, linked to the last guest in this room.',
        'Report issue': 'Open a maintenance ticket for this room. Urgent issues take the room out of service.',
        'Flag priority': 'Mark this room as high priority so it is cleaned first.',
        'Remove priority': 'Clear the high-priority flag on this room.',
        'Record item': 'Save this lost item to the registry for this room.',
        'Save item': 'Save this item to the lost and found registry.',
        'Submit report': 'Send this maintenance report to the team.',
        'Save log': 'Save this activity log entry.',
        'Search logs': 'Find log entries that match your search.',
        'Search': 'Search the list for matching records.',
        'Filter': 'Apply the selected filters to the list.',
        'Filter rooms': 'Show only the rooms that match the selected filters.',
        'Filter users': 'Show only the users that match the selected filters.',
        'Filter payments': 'Show only the payments that match the selected filters.',
        'Apply': 'Apply the selected options.',
        'Apply filters': 'Show results that match the selected filters.',
        'Apply Filters': 'Show results that match the selected filters.',
        'Apply date range': 'Show results for the chosen dates.',
        'Clear filter': 'Remove the filter and show everything.',
        'Clear order': 'Remove the current sort order.',
        'Show': 'Show the hidden details.',

        // Staff management
        'Save role': 'Save the new role for this team member.',
        'Revoke access': 'Block this person from signing in. Their record is kept and access can be restored.',
        'Restore access': 'Allow this person to sign in again.',
        'Remove': 'Permanently delete this record. For staff, use Revoke access to keep the record.',
        'Remove item': 'Take this item off the list.',
        'Cancel invitation': 'Cancel this invitation so its link stops working.',
        'Copy Link': 'Copy the one-time invitation link to the clipboard.',
        'Copy': 'Copy this to the clipboard.',
        'Invite staff': 'Create a one-time invitation for a new staff member.',
        'Create invitation': 'Create a one-time invitation link for a new staff member.',
        'Create member': 'Add this team member.',
        'Add Team Member': 'Add a new member to the Super Admin team.',
        'Save access': 'Save the account status for this staff member.',
        'Save guest': 'Save the guest details.',
        'Save room': 'Save this room.',
        'Save category': 'Save this room category.',
        'Save Hotel': 'Save this hotel’s details.',
        'Save Payment': 'Save this payment record.',
        'Save changes': 'Save your changes.',
        'Save Changes': 'Save your changes.',
        'Save settings': 'Save these settings for the hotel.',
        'Save feature configuration': 'Save which modules are enabled for this hotel.',
        'Save Profile': 'Save your profile details and picture.',
        'Save': 'Save your changes.',

        // Front desk, check-in and check-out
        'Check in': 'Check the guest in and mark the room as occupied.',
        'Check out': 'Open the checkout to settle the bill and release the room.',
        'Check-out history': 'View guests who have already checked out.',
        'Settle & check out': 'Record payment for the outstanding balance and check the guest out.',
        'Pay & check out': 'Take payment for the balance, then check the guest out.',
        'Confirm & post charges': 'Post these charges to the guest folio.',
        'Add Charge': 'Add a charge to this guest’s folio.',
        'Add custom charge': 'Add a one-off charge to the guest folio.',
        'Record payment': 'Save this payment against the guest folio.',
        'Record Salary': 'Record a salary payment for a staff member.',
        'Submit payment for approval': 'Send this payment to the platform team to verify.',
        'View Folio': 'Open the guest folio with all charges and payments.',
        'Place order': 'Send this order to the kitchen.',
        'Add food or room-service item': 'Add a new food or room-service item to the catalog.',
        'Add menu item': 'Add a new item to this menu.',
        'Manage food menu': 'Open the food menu to add or edit items.',
        'Manage menu catalog': 'Open the menu catalog to manage items and prices.',
        'Add selected items': 'Add the selected items to the order.',
        'Open front desk': 'Go to the front desk dashboard.',
        'Open shift': 'Start a cash shift for this drawer.',
        'Open shift desk': 'Go to the cash shift desk.',
        'Close shift': 'Count the drawer and close the cash shift.',
        'Reservations': 'Open the reservations list.',
        'New Booking': 'Start a new booking.',
        '+ New reservation': 'Start a new reservation.',
        'Create reservation': 'Save this reservation.',
        'Create the first reservation': 'Start your first reservation.',
        'Current check-outs': 'Guests due to check out today.',
        'Add room': 'Add a room to this hotel.',
        'Add category': 'Add a room category, such as Standard or Deluxe.',
        'Edit': 'Open this record to change its details.',
        'Edit category': 'Change this room category’s details.',
        'Delete': 'Permanently delete this record.',
        'Delete category': 'Permanently delete this room category. It cannot be undone.',
        'Rooms & categories': 'Open the rooms and categories list.',
        'Back to rooms': 'Return to the rooms list.',
        'Back to categories': 'Return to the room categories.',
        'Back to dashboard': 'Return to your dashboard.',
        'Back to financial overview': 'Return to the financial overview.',
        'Back': 'Go back to the previous page.',
        'View Reports': 'Open the reports for this hotel.',
        'Analytics': 'Open the analytics for this hotel.',

        // Night audit and shifts
        'Run night audit': 'Close the day: checks cash shifts, rooms and billing before the business date rolls over.',
        'Night audit': 'Open the night audit checks for the business day.',
        'Mark paid': 'Mark this subscription payment as received and activate the plan.',

        // Hotel owner and admin
        'Manage subscription': 'Open the subscription and billing page.',
        'Manage Subscription': 'Open the subscription and billing page.',
        'View audit logs': 'Open the audit log of important actions in this hotel.',
        'Online access': 'Open the online access settings for this hotel.',
        'View capabilities': 'See which actions each role is allowed to perform.',
        'Impersonate': 'Open this hotel workspace as its administrator to investigate. Every action is logged.',
        'View as hotel': 'Open this hotel workspace in a new tab to see what its staff see.',
        'Extend subscription': 'Add more time to this hotel subscription.',
        'Extend Subscription': 'Add more time to this hotel subscription.',
        'Extend grace period': 'Give this hotel more days before access is restricted.',
        'Change plan': 'Move this hotel to a different subscription plan.',
        'Create plan': 'Create a new subscription plan.',
        'Suspend hotel': 'Suspend this hotel. Its users lose access until it is reactivated.',
        'Suspend': 'Suspend this account. The user loses access until it is reactivated.',
        'Activate': 'Reactivate this account or hotel.',
        'Approve': 'Approve this request and activate what it pays for.',
        'Reject': 'Reject this request. A review note is required.',
        'Refund': 'Mark this payment as refunded in the ledger.',
        'Send password reset': 'Email this user a link to set a new password.',
        'Reset password': 'Send a link to reset this password.',
        'Reset': 'Clear the form back to its default values.',
        'Rotate left': 'Rotate the picture 90 degrees to the left.',
        'Rotate right': 'Rotate the picture 90 degrees to the right.',
        'Next': 'Go to the next page.',
        'Hotel': 'Open the hotel list.',
        'Add Hotel': 'Register a new hotel.',
        'Add New Hotel': 'Register a new hotel.',
        'Create Hotel Account': 'Create a hotel account for the new owner.',
        'Create Super Admin Account': 'Create a Super Admin console account.',
        'Create account & join hotel': 'Create your account and join this hotel.',
        'Register Hotel': 'Register this hotel with the platform.',
        'Register a hotel account': 'Register a new hotel account.',
        'Register new account': 'Create a new account.',
        'Register': 'Create your account.',
        'Sign in to dashboard': 'Sign in to your dashboard.',
        'Continue to Hotel Dashboard': 'Go to your hotel dashboard.',
        'Continue to 10-day trial →': 'Start your 10-day free trial.',
        'Start a free 10-day trial': 'Start your 10-day free trial.',
        'Reservations': 'Open the reservations list.',
        'Request a free demo': 'Ask for a free product demo.',
        'Request a Demo': 'Ask for a product demo.',
        'Request a walkthrough': 'Book a guided walkthrough of the product.',
        'Connect to Expert →': 'Talk to an expert about your setup.',
        'CONNECT TO EXPERT': 'Talk to an expert about your setup.',
        'Pricing': 'View the pricing plans.',
        'Create Super Admin': 'Create a Super Admin console account.',
        'Mark all as read': 'Mark every notification as seen.',
        'Mark read': 'Mark this notification as seen.',
        'Team Salaries': 'Open the team salary records.',
        'Tenant Payments': 'Open the payments made by hotels.',

        // Navigation and interface
        'Log out': 'Sign out of this account.',
        'Logout': 'Sign out of this account.',
        'Sign out': 'Sign out of this account.',
        'Dark mode': 'Switch between light and dark theme.',
        'Menu': 'Open the navigation menu.',
        'Open menu': 'Open the navigation menu.',
        'Switch to menu view': 'Switch to the side menu layout.',
        'Switch to sidebar': 'Switch to the side menu layout.',
        'Manage': 'Open the management options for this item.',
        'Experience': 'Explore this feature.',
        'Cancel': 'Close without saving.',
        'Close': 'Close this dialog without saving.',
        'Confirm': 'Confirm this action.',
        'Continue': 'Continue to the next step.',
        'Submit': 'Send this form.',
        'Local role switcher −': 'Hide the local role switcher.',
        'Dev Admin': 'Open the local test account for this role.',

        // Icon-only controls
        '×': 'Close this dialog.',
        '✕': 'Close this dialog.',
        '−': 'Collapse this panel.',
        '+': 'Add a new item.',
        '⋯': 'More actions.',
        '⌃': 'Expand this panel.',
        '▾': 'Open the options.',
        '⌄': 'Open the options.'
    };

    var tip = null;
    var showing = null;

    function ensureTip() {
        if (tip) return tip;
        tip = document.createElement('div');
        tip.className = 'ui-action-tip';
        tip.setAttribute('role', 'tooltip');
        document.body.appendChild(tip);
        return tip;
    }

    function labelOf(el) {
        return (el.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function tipFor(el) {
        if (el.dataset.tip) return el.dataset.tip;
        if (el.hasAttribute('data-no-tip')) return '';

        var label = labelOf(el);
        var described = DESCRIPTIONS[label] || DESCRIPTIONS[label.replace(/\s*[→›»▾⌄]+$/, '').trim()];
        if (described) return described;

        var named = el.getAttribute('aria-label') || el.getAttribute('title') || label;
        return named ? 'Runs this action: ' + named.replace(/\s+/g, ' ').trim() + '.' : 'Runs this action.';
    }

    function place(el) {
        var node = ensureTip();
        var rect = el.getBoundingClientRect();
        node.style.left = '0px';
        node.style.top = '0px';
        node.classList.add('is-visible');
        var width = node.offsetWidth;
        var height = node.offsetHeight;
        var gap = 8;
        var left = rect.left + rect.width / 2 - width / 2;
        left = Math.max(8, Math.min(left, window.innerWidth - width - 8));
        var top = rect.top - height - gap;
        if (top < 8) top = rect.bottom + gap;
        node.style.left = Math.round(left) + 'px';
        node.style.top = Math.round(top) + 'px';
    }

    function show(el) {
        var node = ensureTip();
        node.textContent = tipFor(el);
        showing = el;
        place(el);
    }

    function hide() {
        if (tip) tip.classList.remove('is-visible');
        showing = null;
    }

    // Buttons always get a tooltip. Links only when they are styled as buttons or carry data-tip.
    function target(node) {
        if (!node || !node.closest) return null;
        var el = node.closest('button, a, [role="button"], [data-tip]');
        if (!el) return null;
        if (el.tagName === 'A' && !el.dataset.tip && !/button|btn|chip|pill/.test(el.className)) return null;
        if (el.disabled) return null;
        return el;
    }

    document.addEventListener('pointerover', function (event) {
        var el = target(event.target);
        if (el && el !== showing) show(el);
    });
    document.addEventListener('pointerout', function (event) {
        var el = target(event.target);
        if (el && el === showing && !el.contains(event.relatedTarget)) hide();
    });
    document.addEventListener('focusin', function (event) {
        var el = target(event.target);
        if (el) show(el);
    });
    document.addEventListener('focusout', hide);
    document.addEventListener('scroll', hide, true);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') hide();
    });

    var style = document.createElement('style');
    style.textContent = [
        '.ui-action-tip{position:fixed;z-index:10000;max-width:260px;padding:8px 11px;border-radius:9px;background:#0f172a;color:#f8fafc;font:500 12px/1.45 ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;box-shadow:0 10px 30px rgba(15,23,42,.28);pointer-events:none;opacity:0;transform:translateY(4px);transition:opacity .15s ease,transform .15s ease}',
        '.ui-action-tip.is-visible{opacity:1;transform:translateY(0)}',
        '@media (prefers-reduced-motion:reduce){.ui-action-tip{transition:none}}'
    ].join('');
    document.head.appendChild(style);
})();
