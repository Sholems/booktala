<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Comprehensive BookTala Pages Seeder.
 * Populates detailed, legally robust, hospitality-grade policy pages
 * and informational resources for BookTala (operated by G-Briel Property Ltd).
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $brand = config('brand.name', 'BookTala');
        $owner = config('brand.owner', 'G-Briel Property Ltd');
        $phone = config('brand.phone', '+234 800 000 0000');
        $email = config('brand.email', 'support@booktala.com');
        $address = config('brand.address', 'Lagos, Nigeria');

        $pages = [
            [
                'title' => 'Terms and Conditions',
                'slug' => 'terms-and-conditions',
                'meta_title' => "Terms and Conditions - {$brand} Luxury Stays & Short-Lets",
                'meta_description' => "Read the full terms and conditions governing reservations, payments, guest identification, caution deposits, and stay guidelines at {$brand}.",
                'content' => <<<HTML
<h2>1. Introduction & Agreement to Terms</h2>
<p>Welcome to <strong>{$brand}</strong>, a premium short-let hospitality and residential management platform operated by <strong>{$owner}</strong> ("{$brand}", "we", "us", or "our"), incorporated under the laws of the Federal Republic of Nigeria.</p>
<p>These Terms and Conditions ("Terms") constitute a legally binding agreement between you ("Guest", "Resident", "you", or "your") and {$brand}. By accessing our website, browsing our portfolio, completing a reservation, or checking into any of our residences, serviced apartments, suites, or villas, you acknowledge that you have read, understood, and agree to be bound by these Terms, as well as our <a href="/privacy-policy">Privacy Policy</a>, <a href="/cancellation-policy">Cancellation & Refund Policy</a>, <a href="/house-rules">House Rules</a>, and <a href="/security-deposit-policy">Security Deposit Policy</a>.</p>

<h2>2. Eligibility & Guest Account Responsibilities</h2>
<ul>
    <li><strong>Age Requirement:</strong> You must be at least eighteen (18) years of age to book or check into any {$brand} residence. Minors under 18 must be accompanied at all times by a parent or legal guardian.</li>
    <li><strong>Account Integrity:</strong> You warrant that all information provided during registration and booking (including legal name, email address, mobile phone number, and guest counts) is true, accurate, current, and complete.</li>
    <li><strong>Identity Verification:</strong> Guests are strictly prohibited from booking under false identities, fictitious names, or using fraudulent payment credentials.</li>
</ul>

<h2>3. Reservation Confirmation, Nightly Rates & Taxes</h2>
<ul>
    <li><strong>Binding Contract:</strong> A reservation is confirmed and legally binding only upon our successful receipt of the required accommodation fee and the issuance of an official {$brand} Booking Confirmation voucher containing a unique Booking Reference number.</li>
    <li><strong>Pricing Structure:</strong> Nightly rates are calculated based on property type, dates, season, length of stay, and designated guest tier. Rates are quoted in Nigerian Naira (NGN) and/or United States Dollars (USD) as displayed on the booking portal.</li>
    <li><strong>Statutory VAT:</strong> In compliance with the Federal Inland Revenue Service (FIRS) and applicable state revenue authorities, statutory Value Added Tax (VAT) of 7.5% and any applicable state consumption taxes are included or itemized transparently prior to checkout.</li>
    <li><strong>Secure Payment Processing:</strong> All online credit card, debit card, and direct bank transfer payments are securely processed through our PCI-DSS Level 1 certified payment partner, <strong>Paystack</strong>. {$brand} never stores or accesses your raw card numbers, CVVs, or bank security PINs.</li>
</ul>

<h2>4. Mandatory Guest Identification & Security Clearance (KYC)</h2>
<p>In accordance with Nigerian hospitality regulations, estate security requirements, and anti-fraud mandates, <strong>every primary guest must complete identity verification before key handover or smart lock code delivery:</strong></p>
<ul>
    <li><strong>Accepted Documents:</strong> A valid Nigerian National Identity Management Commission (NIMC) NIN Slip, Nigerian Voter's Card, Nigerian Driver's License, or a valid International Passport.</li>
    <li><strong>Check-in Roster:</strong> The full names and contact numbers of all accompanying overnight guests must be registered with management prior to arrival.</li>
    <li><strong>Refusal of Access:</strong> {$brand} reserves the strict right to withhold property access without refund if the guest refuses to present matching government-issued identification upon check-in.</li>
</ul>

<h2>5. Check-In, Check-Out & Inspection Protocol</h2>
<ul>
    <li><strong>Standard Check-In Time:</strong> Check-in commences from <strong>2:00 PM (14:00 West Africa Time)</strong>. Early check-in is subject to availability and prior written confirmation, and may incur an early arrival surcharge.</li>
    <li><strong>Standard Check-Out Time:</strong> Check-out must be concluded strictly by <strong>11:00 AM (11:00 WAT)</strong> to enable our professional housekeeping team to decontaminate, sanitize, and prepare the residence for subsequent arrivals.</li>
    <li><strong>Unauthorized Late Check-Out:</strong> Any unapproved delay past 11:00 AM disrupts scheduled housekeeping and incoming guests. An automated fee of ₦15,000 per hour will be deducted from your caution deposit. Unapproved occupancy extending past 2:00 PM will be billed at the full prevailing daily rack rate.</li>
    <li><strong>Turnover Inspection:</strong> Our housekeeping supervisors inspect each residence within two (2) hours following check-out. Any damaged, missing, or compromised assets will be documented with photographic evidence.</li>
</ul>

<h2>6. Maximum Occupancy, Registered Guests & Day Visitors</h2>
<ul>
    <li><strong>Overnight Limits:</strong> Each residence has an enforceable maximum occupancy rating (e.g., maximum 2 guests for a 1-bedroom suite; maximum 4 guests for a 2-bedroom suite). Under no circumstances may additional overnight occupants reside on the premises without prior written authorization and payment of supplementary guest charges.</li>
    <li><strong>Day Visitors:</strong> Day visitors are welcome between <strong>9:00 AM and 10:00 PM</strong>. All visitors must be registered at the estate security post and must present valid identification. Visitors remaining on the premises past 10:00 PM will be classified as unauthorized overnight occupants and billed accordingly.</li>
</ul>

<h2>7. Refundable Caution / Security Deposit</h2>
<p>To preserve our luxury assets, furnishings, and art collections, every guest must provide a refundable caution deposit prior to arrival. The deposit is held in escrow and released within twenty-four to forty-eight (24–48) hours following check-out inspection, provided the property is returned in good order. Please review our detailed <a href="/security-deposit-policy">Security Deposit Policy</a> for deduction triggers, inventory fees, and refund timelines.</p>

<h2>8. Smoke-Free Residence Policy & Decontamination Fee</h2>
<ul>
    <li><strong>Strictly Non-Smoking:</strong> All {$brand} residential interiors, bedrooms, living rooms, and bathrooms are <strong>100% smoke-free and vape-free zones</strong>. This includes cigarettes, cigars, electronic vaporizers, shisha/hookah pipes, and cannabis.</li>
    <li><strong>Designated Smoking Areas:</strong> Smoking is permitted strictly on external private balconies or outdoor garden terraces with all glass sliding doors firmly closed to prevent smoke intrusion into the living areas.</li>
    <li><strong>Mandatory Decontamination Fee:</strong> Any evidence of indoor smoking, burning marks, or residual smoke odor will result in an immediate, non-negotiable <strong>₦50,000 professional ozone deodorization and deep fabric steam-cleaning charge</strong> deducted directly from your caution deposit.</li>
</ul>

<h2>9. Noise Curfew, Community Living & Anti-Party Ban</h2>
<ul>
    <li><strong>Quiet Hours:</strong> In consideration of neighbors and estate residents, strict quiet hours are enforced between <strong>10:00 PM and 7:00 AM</strong> daily. Sound systems, televisions, and conversations must be kept to respectful ambient levels.</li>
    <li><strong>Zero Tolerance for Unauthorized Parties:</strong> Our residences are designed for peaceful living, corporate stays, and family holidays. <strong>Unauthorized loud parties, clubbing events, and disruptive drinking gatherings are strictly prohibited.</strong></li>
    <li><strong>Enforcement & Eviction:</strong> Violation of noise rules or hosting unauthorized parties triggers an immediate warning from estate security. Failure to comply will lead to immediate police/security shutdown, immediate eviction without refund of remaining nights, and complete forfeiture of the caution deposit.</li>
    <li><strong>Authorized Event Bookings:</strong> If you wish to host an intimate dinner, bridal shower, or brand gathering, you must explicitly book an <em>Event Stay</em> with prior written management approval, bespoke guest limits, and applicable event sanitation surcharges.</li>
</ul>

<h2>10. Filming, Photography & Commercial Content Creation</h2>
<p>Commercial film shoots, music video productions, public influencer brand endorsements, and ticketed events require prior commercial licensing from {$brand} management. Unauthorized commercial shoots or video crew setups inside our apartments will result in immediate termination of the reservation and a retroactive commercial location penalty of ₦250,000.</p>

<h2>11. Utilities, Fiber Wi-Fi & 24/7 Power Assurance</h2>
<ul>
    <li><strong>Guaranteed Continuous Power:</strong> {$brand} operates an uninterrupted multi-tier power redundancy system comprising public utility grids, central soundproof industrial diesel generators, and automated inverter battery systems. Scheduled generator maintenance windows are communicated in advance.</li>
    <li><strong>High-Speed Internet:</strong> Complimentary high-speed fiber-optic Wi-Fi is provided for guest productivity and entertainment. Guests agree not to utilize our network for illegal cyber activities, torrenting copyrighted materials, or cyber harassment.</li>
    <li><strong>Resource Conservation:</strong> Guests are respectfully requested to switch off high-draw air conditioning units and water heaters when departing the residence for excursions or meetings.</li>
</ul>

<h2>12. Prohibited & Unlawful Activities</h2>
<p>The following activities are strictly prohibited and result in immediate lease termination, eviction without notice, and reporting to Nigerian law enforcement agencies:</p>
<ul>
    <li>Possession, distribution, or consumption of illegal narcotics or controlled substances.</li>
    <li>Commercial prostitution, sex work, or solicitation.</li>
    <li>Cybercrime, fraud, unauthorized wiretapping, or money laundering activities.</li>
    <li>Possession of unlicensed firearms, explosive materials, or dangerous weapons.</li>
    <li>Harassment, physical abuse, verbal threats, or intimidation of building security, cleaning staff, or neighbors.</li>
</ul>

<h2>13. Limitation of Liability & Personal Belongings</h2>
<ul>
    <li><strong>Personal Valuables:</strong> {$brand} and {$owner} assume no liability for loss, theft, or damage to personal valuables, jewelry, electronics, or cash left inside residences or vehicles on the premises. Guests are urged to lock all doors, secure balcony latches, and utilize available in-room electronic safes.</li>
    <li><strong>Service Interruptions:</strong> While we maintain rigorous contingency infrastructure, {$brand} is not liable for temporary utility interruptions originating from municipal public grids, national telecommunication carrier fiber cuts, or municipal water boards beyond our immediate operational control.</li>
    <li><strong>Force Majeure:</strong> Neither party shall be liable for failure to perform obligations due to acts of God, civil unrest, regional power emergencies, government lockdown mandates, epidemics, or natural disasters.</li>
</ul>

<h2>14. Governing Law & Dispute Resolution</h2>
<p>These Terms shall be governed by, construed, and enforced in accordance with the substantive laws of the <strong>Federal Republic of Nigeria</strong>. Any controversy, dispute, or claim arising out of or relating to these Terms, booking contracts, or stays that cannot be resolved amicably within fourteen (14) days shall be subject to the exclusive jurisdiction of the competent courts of <strong>Lagos State, Nigeria</strong>.</p>

<h2>15. Contact & Legal Enquiries</h2>
<p>For questions, formal notices, or clarifications regarding these Terms and Conditions, please contact our legal and guest compliance desk:</p>
<p><strong>{$owner} (Operating {$brand})</strong><br>
Email: <a href="mailto:{$email}">{$email}</a><br>
Direct Phone / WhatsApp: <a href="tel:{$phone}">{$phone}</a><br>
Address: {$address}</p>
HTML
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'meta_title' => "Privacy Policy & Data Protection - {$brand}",
                'meta_description' => "Learn how {$brand} collects, encrypts, and protects your personal and financial information under the Nigeria Data Protection Act (NDPA) and global privacy standards.",
                'content' => <<<HTML
<h2>1. Commitment to Privacy & Legal Framework</h2>
<p><strong>{$brand}</strong>, a hospitality and residential management brand of <strong>{$owner}</strong> ("we", "us", or "our"), is dedicated to safeguarding the privacy, confidentiality, and data sovereignty of all our guests, property owners, and website visitors.</p>
<p>This Privacy Policy explains how we collect, process, store, disclose, and protect your personal data when you interact with our website, book our luxury accommodations, utilize our concierge services, or communicate with our operations team. We comply fully with the <strong>Nigeria Data Protection Act (NDPA 2023)</strong>, the <strong>Nigeria Data Protection Regulation (NDPR)</strong>, and international data protection principles including the EU/UK <strong>General Data Protection Regulation (GDPR)</strong> where applicable to international travelers.</p>

<h2>2. The Data Controller</h2>
<p>The entity responsible for processing your personal data is:<br>
<strong>{$owner} (Trading as {$brand})</strong><br>
Registered Address: {$address}<br>
Data Protection Desk: <a href="mailto:{$email}">{$email}</a><br>
Guest Hotline: <a href="tel:{$phone}">{$phone}</a></p>

<h2>3. Information We Collect</h2>
<p>We collect personal information necessary to deliver exceptional hospitality services, facilitate secure online bookings, comply with statutory guest registries, and ensure security across our properties:</p>
<ul>
    <li><strong>Personal Contact Data:</strong> Full legal name, residential address, telephone/WhatsApp number, and email address.</li>
    <li><strong>Government Identification (KYC):</strong> Scanned copies or photographs of your National Identification Number (NIN) slip, International Passport, Driver's License, or Voter's Card, along with date of birth and nationality.</li>
    <li><strong>Reservation & Stay Details:</strong> Check-in and check-out dates, selected apartment or villa, booking reference number, number of accompanying guests, special requests, dietary preferences, and room service orders.</li>
    <li><strong>Financial & Payment Transaction Data:</strong> Transaction reference tokens, billing addresses, bank transfer receipts, and payment method details provided through Paystack. <em>Please note that we never store full debit/credit card numbers or CVVs on our servers.</em></li>
    <li><strong>Concierge & In-Stay Requests:</strong> Flight itinerary details for airport transfers, vehicle registration numbers for parking access, laundry preferences, and housekeeping notes.</li>
    <li><strong>Technical & Analytical Information:</strong> IP address, device type, browser specifications, geographical location, operating system, and website navigation paths collected via session cookies.</li>
</ul>

<h2>4. How We Use Your Personal Data</h2>
<p>Your data is processed strictly for legitimate operational, contractual, and regulatory purposes, including:</p>
<ul>
    <li><strong>Reservation Fulfillment:</strong> Generating booking confirmations, processing payments, reserving dates, and managing guest folios.</li>
    <li><strong>Guest Clearance & Estate Access:</strong> Registering your vehicle and guest names with estate gate security personnel for seamless entry credentials.</li>
    <li><strong>Automated Guest Messaging:</strong> Dispatching check-in access instructions, digital smart lock codes, Wi-Fi passwords, invoice receipts, and checkout reminders via Brevo email delivery and WhatsApp.</li>
    <li><strong>In-Stay Concierge Services:</strong> Delivering in-room dining orders, organizing airport chauffeur pickups, processing laundry requests, and coordinating extra housekeeping.</li>
    <li><strong>Safety, Asset Protection & KYC:</strong> Verifying guest identity against fraudulent transactions, managing caution deposits, and complying with statutory hotel register laws.</li>
    <li><strong>Service Enhancement:</strong> Analyzing feedback, reviews, and website usage to optimize our booking engine, improve guest comfort, and introduce new amenities.</li>
</ul>

<h2>5. Payment Processing & PCI-DSS Security</h2>
<p>All online payment transactions are processed through <strong>Paystack</strong>, a certified Payment Card Industry Data Security Standard (PCI-DSS) Level 1 service provider. When you enter card information during checkout, it is encrypted and transmitted directly to Paystack's secure payment vault. {$brand} only receives a cryptographic authorization token and payment reference to verify transaction settlement.</p>

<h2>6. Disclosure & Third-Party Sharing</h2>
<p>We do not sell, rent, trade, or monetize your personal information to third parties under any circumstances. We share your data strictly with trusted service partners under binding non-disclosure and data protection agreements:</p>
<ul>
    <li><strong>Payment Gateways:</strong> Paystack Payments Limited for processing debit/credit card and bank transfer transactions.</li>
    <li><strong>Automated Communication Platforms:</strong> Brevo (formerly Sendinblue) for transactional and marketing emails; Termii and Twilio for automated WhatsApp alerts and SMS notifications.</li>
    <li><strong>Estate Security & Facility Management:</strong> Providing guest names and registered vehicle numbers to gated estate security posts to facilitate authorized entry clearance.</li>
    <li><strong>Law Enforcement & Statutory Bodies:</strong> Where required by Nigerian law, valid court subpoenas, the Economic and Financial Crimes Commission (EFCC), or Nigeria Police Force investigations.</li>
</ul>

<h2>7. Data Security & Technical Safeguards</h2>
<p>We deploy robust enterprise-grade technical and organizational security measures to protect your information against unauthorized access, loss, alteration, or disclosure:</p>
<ul>
    <li>End-to-end 256-bit Transport Layer Security (TLS/SSL) encryption on all website pages, forms, and APIs.</li>
    <li>Encrypted database storage and strict password hashing using bcrypt cryptographic algorithms.</li>
    <li>Role-based access control (RBAC), ensuring only authorized administrative personnel can access guest folios and identification records.</li>
    <li>Regular automated cloud database backups and continuous server vulnerability scans.</li>
</ul>

<h2>8. Data Retention Policies</h2>
<ul>
    <li><strong>Accounting & Tax Records:</strong> Folio statements, invoices, and transaction logs are retained for six (6) years in accordance with Nigerian tax laws and auditing regulations.</li>
    <li><strong>Government Identification Records:</strong> Scanned KYC identity documents are securely archived during your stay and purged within ninety (90) days following clean departure inspection, unless required for ongoing legal dispute resolution.</li>
    <li><strong>Marketing Records:</strong> Retained until you choose to unsubscribe from our newsletter or promotional communications.</li>
</ul>

<h2>9. Your Legal Rights as a Data Subject</h2>
<p>Under the Nigeria Data Protection Act (NDPA) and applicable privacy regulations, you possess enforceable rights regarding your personal data:</p>
<ul>
    <li><strong>Right of Access:</strong> You may request a copy of the personal information we hold about you.</li>
    <li><strong>Right to Rectification:</strong> You may request that inaccurate, incomplete, or outdated data be corrected immediately.</li>
    <li><strong>Right to Erasure ("Right to be Forgotten"):</strong> You may request deletion of your personal data when it is no longer required for booking fulfillment or statutory tax compliance.</li>
    <li><strong>Right to Restrict or Object:</strong> You may object to the processing of your data for direct marketing or profiling.</li>
    <li><strong>Right to Data Portability:</strong> You may request your data in a structured, commonly used machine-readable format.</li>
</ul>
<p>To exercise any of these rights, please submit a written request to our Data Protection Desk at <a href="mailto:{$email}">{$email}</a>. We respond to all verified requests within thirty (30) days.</p>

<h2>10. Cookie Policy</h2>
<p>Our website utilizes essential and analytical cookies to ensure smooth site navigation, remember your search dates, maintain authentication sessions, and evaluate website performance. You can manage or disable non-essential cookies through your browser settings, though certain website functions may be limited as a result.</p>

<h2>11. Policy Revisions & Updates</h2>
<p>We review and update this Privacy Policy periodically to reflect technological advancements, operational enhancements, or statutory legal amendments. Any revisions will be published on this page with an updated effective date.</p>
HTML
            ],
            [
                'title' => 'Cancellation Policy',
                'slug' => 'cancellation-policy',
                'meta_title' => "Cancellation & Refund Policy - {$brand}",
                'meta_description' => 'Transparent cancellation windows, refund timelines, date rescheduling options, and force majeure coverage for BookTala reservations.',
                'content' => <<<HTML
<h2>1. Overview of Our Booking & Cancellation Philosophy</h2>
<p>At <strong>{$brand}</strong>, operated by <strong>{$owner}</strong>, we recognize that business schedules, flight plans, and family itineraries can change unexpectedly. Our Cancellation and Refund Policy is designed to offer maximum fairness, transparency, and clarity to our guests while protecting our property owners and operations team from last-minute calendar vacancies.</p>

<h2>2. Standard Short-Let Cancellation Schedule</h2>
<p>Unless an individual property or seasonal promotion explicitly specifies an alternative policy on your reservation voucher, the following cancellation tiers apply to all direct bookings made through {$brand}:</p>

<table style="width: 100%; border-collapse: collapse; margin: 1.5rem 0;">
    <thead>
        <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
            <th style="padding: 12px; font-size: 0.9rem; color: #1e293b;">Cancellation Timing</th>
            <th style="padding: 12px; font-size: 0.9rem; color: #1e293b;">Accommodation Refund</th>
            <th style="padding: 12px; font-size: 0.9rem; color: #1e293b;">Caution Deposit Refund</th>
        </tr>
    </thead>
    <tbody>
        <tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 12px; font-weight: bold; color: #059669;">More than 72 hours before check-in (2:00 PM)</td>
            <td style="padding: 12px; color: #059669; font-weight: bold;">100% Full Refund <span style="font-size: 0.8rem; font-weight: normal; color: #64748b;">(less payment gateway fee)</span></td>
            <td style="padding: 12px; color: #059669; font-weight: bold;">100% Full Refund</td>
        </tr>
        <tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 12px; font-weight: bold; color: #d97706;">Between 72 hours and 24 hours before check-in</td>
            <td style="padding: 12px; color: #d97706; font-weight: bold;">50% Partial Refund</td>
            <td style="padding: 12px; color: #059669; font-weight: bold;">100% Full Refund</td>
        </tr>
        <tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 12px; font-weight: bold; color: #dc2626;">Less than 24 hours before check-in or No-Show</td>
            <td style="padding: 12px; color: #dc2626; font-weight: bold;">0% Non-Refundable</td>
            <td style="padding: 12px; color: #059669; font-weight: bold;">100% Full Refund</td>
        </tr>
    </tbody>
</table>

<h2>3. Refund of Caution / Security Deposits</h2>
<p><strong>Caution deposits are 100% refundable under all cancellation scenarios.</strong> If a reservation is cancelled prior to arrival—even within the non-refundable accommodation window—your caution deposit will be refunded in full without any deductions whatsoever, as no property occupancy or wear occurred.</p>

<h2>4. Long-Term Stays (7+ Nights & Monthly Bookings)</h2>
<p>For extended stays and corporate reservations exceeding seven (7) consecutive nights, discounted long-stay rates are applied. Consequently, a minimum of <strong>seven (7) days written notice</strong> prior to scheduled check-in is required to receive a full refund. Cancellations made with less than 7 days notice will be charged a penalty equivalent to three (3) nights accommodation.</p>

<h2>5. Peak Season, December Holiday & Special Event Bookings</h2>
<p>Reservations falling within designated high-demand periods—including the December holiday season (<strong>"Detty December"</strong> between December 15 and January 10), Easter long weekends, and designated festive holidays—are classified as <strong>Strictly Non-Refundable</strong>. Because these dates experience unprecedented demand and properties are held exclusively for you, cancellations during peak periods forfeit 100% of the accommodation fee.</p>

<h2>6. Rescheduling & Stay Credit Option (Alternative to Penalty)</h2>
<p>To avoid forfeiture of funds, {$brand} offers a flexible <strong>Rescheduling & Stay Credit Alternative:</strong></p>
<ul>
    <li><strong>One-Time Date Rescheduling:</strong> If your travel plans change, you may request a one-time date modification up to forty-eight (48) hours prior to arrival, subject to property availability and any seasonal rate variance.</li>
    <li><strong>BookTala Stay Credit Voucher:</strong> You may elect to convert your booking balance into a digital Stay Credit Voucher valid for twelve (12) calendar months from the date of issue, redeemable across any available property in our portfolio.</li>
</ul>

<h2>7. Early Departures & Shortened Stays</h2>
<p>Once a guest has checked into an apartment, early departures, voluntary checkouts, or shortened stays are classified as non-refundable. The remaining unused nights cannot be credited or refunded, as the apartment calendar was blocked and unavailable to other prospective travelers.</p>

<h2>8. Extenuating Circumstances & Force Majeure</h2>
<p>We review cancellation requests with compassion in verified extenuating circumstances beyond a guest's reasonable control. Eligible situations include:</p>
<ul>
    <li>Sudden severe medical hospitalization or bereavement of an immediate family member (substantiated by certified medical documentation).</li>
    <li>Official government-mandated travel bans, state border closures, or emergency curfews.</li>
    <li>Airline-initiated domestic or international flight cancellations directly impacting arrival.</li>
    <li>Natural disasters, widespread civil disruptions, or severe infrastructure failures.</li>
</ul>
<p>In approved extenuating circumstance cases, {$brand} will issue a full Stay Credit voucher or a refund less nominal third-party payment gateway transaction fees.</p>

<h2>9. Refund Disbursement Timelines & Methods</h2>
<ul>
    <li><strong>Original Payment Method:</strong> All refunds are disbursed exclusively back to the original bank account or card used to fund the transaction via our payment partner, Paystack. We do not issue cash refunds or third-party bank transfers to prevent financial fraud.</li>
    <li><strong>Processing Window:</strong> Once approved by management, refunds are initiated within <strong>twenty-four (24) hours</strong>. Depending on your financial institution and interbank settlement channels, funds reflect in your account within <strong>three to seven (3–7) business banking days</strong>.</li>
</ul>

<h2>10. How to Initiate a Cancellation</h2>
<p>To cancel or reschedule a booking, you can:<br>
1. Log into your <a href="/login">Guest Dashboard</a>, select your active reservation, and click <strong>Cancel Booking</strong>; OR<br>
2. Contact our 24/7 Guest Concierge Desk via WhatsApp or telephone at <a href="tel:{$phone}">{$phone}</a>; OR<br>
3. Send an email with your Booking Reference number to <a href="mailto:{$email}">{$email}</a>.</p>
HTML
            ],
            [
                'title' => 'House Rules & Resident Guidelines',
                'slug' => 'house-rules',
                'meta_title' => "House Rules & Resident Guidelines - {$brand}",
                'meta_description' => 'Essential house rules, quiet hours, smoke-free policies, visitor guidelines, and community etiquette for all BookTala guests.',
                'content' => <<<HTML
<h2>1. Welcome to BookTala Living</h2>
<p>Welcome to your home away from home. <strong>{$brand}</strong> residences are designed to deliver the ultimate blend of boutique luxury, residential privacy, and refined comfort. To ensure a harmonious, safe, and restful stay for all residents, guests, and community neighbors, we request your cooperation with our comprehensive House Rules.</p>

<h2>2. Check-In & Departure Schedule</h2>
<ul>
    <li><strong>Check-In Window:</strong> From <strong>2:00 PM (14:00)</strong> onwards. Self-check-in smart lock access codes or front desk key deliveries are activated only after identity clearance.</li>
    <li><strong>Check-Out Deadline:</strong> Strictly by <strong>11:00 AM (11:00)</strong>. Timely departures allow our professional cleaning crew to sanitize, replace fine linens, and inspect the residence for incoming guests.</li>
    <li><strong>Late Check-Out:</strong> Any unapproved stay beyond 11:00 AM incurs a late departure surcharge of ₦15,000 per hour, billed against your caution deposit.</li>
</ul>

<h2>3. Community Quiet Hours & Noise Courtesy</h2>
<ul>
    <li><strong>Quiet Hours:</strong> Between <strong>10:00 PM and 7:00 AM</strong> daily, residential quiet hours are in effect across all properties.</li>
    <li><strong>Sound Systems & TVs:</strong> High-volume stereos, subwoofers, musical instruments, and loud television audio that penetrates adjacent walls or floors are strictly forbidden.</li>
    <li><strong>Neighborly Respect:</strong> Our residences are located within prestigious, quiet gated estates and executive apartment complexes. Please respect the peace and tranquility of our neighbors.</li>
</ul>

<h2>4. 100% Smoke-Free & Vape-Free Interiors</h2>
<ul>
    <li><strong>No Indoor Smoking:</strong> Smoking of any substance—including cigarettes, cigars, electronic vapes, shisha/hookah, and cannabis—is <strong>strictly prohibited inside the apartment</strong>.</li>
    <li><strong>Balcony Use:</strong> Guests who wish to smoke must do so on external outdoor balconies or ground-floor patios, ensuring the sliding glass door remains firmly closed. Hookah/shisha coals must never be placed directly on furniture or wooden decking.</li>
    <li><strong>Decontamination Penalty:</strong> A mandatory <strong>₦50,000 deep cleaning and ozone deodorization fee</strong> will be deducted immediately from your caution deposit if smoke odor, ash, or cigarette butts are discovered inside.</li>
</ul>

<h2>5. Anti-Party Policy & Social Gatherings</h2>
<ul>
    <li><strong>No Unauthorized Parties:</strong> Parties, loud celebrations, clubbing meetups, and ticketed gatherings are strictly prohibited in standard accommodation bookings.</li>
    <li><strong>Estate Security Enforcement:</strong> Estate security guards are instructed to turn away crowds and shut down unauthorized gatherings immediately. Any violation will result in instant lease termination, eviction without refund, and forfeiture of your caution deposit.</li>
    <li><strong>Event Bookings:</strong> If you wish to host an intimate dinner, birthday gathering, or corporate session, you must contact management in advance to book a designated <em>Event Stay</em> with custom guest allocations and sanitation surcharges.</li>
</ul>

<h2>6. Maximum Occupancy & Visitor Regulations</h2>
<ul>
    <li><strong>Overnight Occupants:</strong> Only guests registered on your booking voucher are permitted to sleep in the apartment overnight. Exceeding designated bed capacity is a breach of safety regulations.</li>
    <li><strong>Day Visitors:</strong> Day visitors are welcome between <strong>9:00 AM and 10:00 PM</strong>. All visitors must be registered at the security gate and must present identification. Visitors may not remain on premises past 10:00 PM.</li>
</ul>

<h2>7. Energy Conservation & Appliance Care</h2>
<ul>
    <li><strong>Eco-Friendly Power Use:</strong> While our properties feature 24/7 uninterrupted power redundancy, guests are kindly requested to turn off air conditioning units, water heaters, and interior lighting when stepping out for excursions or dining.</li>
    <li><strong>Appliance Handling:</strong> Please treat smart televisions, built-in ovens, induction hobs, microwave ovens, washing machines, and refrigerators with care. Operating instructions are provided in our digital guest portal.</li>
</ul>

<h2>8. Swimming Pool & Wellness Facility Rules</h2>
<ul>
    <li><strong>Operating Hours:</strong> Communal swimming pools and fitness centers are open daily from <strong>7:00 AM to 9:00 PM</strong>.</li>
    <li><strong>Glassware Ban:</strong> Glass bottles, glass tumblers, and sharp objects are strictly prohibited in and around swimming pool perimeters to avoid shattering and foot injuries. Please use plastic cups provided.</li>
    <li><strong>Child Safety:</strong> Children under 14 must be supervised by an adult at all times around pool areas. There are no on-duty lifeguards; swimming is at your own risk.</li>
</ul>

<h2>9. Kitchen Care & Waste Disposal</h2>
<ul>
    <li><strong>Trash Management:</strong> Please bag all everyday waste in the supplied bin liners. Trash bags must be placed in designated floor garbage chutes or compound collection bins daily.</li>
    <li><strong>Grease & Food Disposal:</strong> Do not pour cooking grease, heavy oils, or solid food scraps down kitchen sinks to prevent plumbing blockages.</li>
</ul>

<h2>10. Parking, Keys & Smart Access Hardware</h2>
<ul>
    <li><strong>Designated Parking:</strong> Park vehicles exclusively in the assigned parking bays allocated to your apartment number. Do not obstruct neighbor driveways or fire exits.</li>
    <li><strong>Smart Access:</strong> Digital door lock passcodes are confidential to your party. Do not share codes with unauthorized individuals.</li>
    <li><strong>Lost Keys / Fobs:</strong> In the event of a lost physical security key, RFID gate card, or access fob, a replacement and reprogramming fee of <strong>₦15,000 per item</strong> will be charged.</li>
</ul>
HTML
            ],
            [
                'title' => 'Security & Caution Deposit Policy',
                'slug' => 'security-deposit-policy',
                'meta_title' => "Refundable Caution Deposit Policy - {$brand}",
                'meta_description' => 'Complete breakdown of refundable caution deposit amounts, payment timing, turnover inspections, deduction schedules, and prompt refund releases.',
                'content' => <<<HTML
<h2>1. Why We Require a Refundable Caution Deposit</h2>
<p>At <strong>{$brand}</strong>, our residences are outfitted with designer Italian and Scandinavian furnishings, original artwork, high-definition smart home entertainment, luxury orthopaedic mattresses, and premium fitted kitchens. To maintain these exceptional standards and protect against accidental breakages or policy breaches, a <strong>Refundable Caution / Security Deposit</strong> is required for all reservations.</p>
<p>The caution deposit is <em>not</em> an additional fee; it is a temporary, fully refundable security pledge held in escrow and released promptly following your stay.</p>

<h2>2. Standard Caution Deposit Amounts by Property Tier</h2>
<p>Deposit amounts are determined by property size, valuation, and occupancy tier:</p>

<table style="width: 100%; border-collapse: collapse; margin: 1.5rem 0;">
    <thead>
        <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
            <th style="padding: 12px; font-size: 0.9rem; color: #1e293b;">Property Category</th>
            <th style="padding: 12px; font-size: 0.9rem; color: #1e293b;">Standard Deposit (NGN)</th>
            <th style="padding: 12px; font-size: 0.9rem; color: #1e293b;">Standard Deposit (USD)</th>
        </tr>
    </thead>
    <tbody>
        <tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 12px; font-weight: bold;">Executive Studio & 1-Bedroom Suites</td>
            <td style="padding: 12px; color: #0284c7; font-weight: bold;">₦25,000 – ₦35,000</td>
            <td style="padding: 12px; color: #0284c7; font-weight: bold;">$25 – $35</td>
        </tr>
        <tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 12px; font-weight: bold;">Luxury 2-Bedroom & 3-Bedroom Suites</td>
            <td style="padding: 12px; color: #0284c7; font-weight: bold;">₦50,000 – ₦75,000</td>
            <td style="padding: 12px; color: #0284c7; font-weight: bold;">$50 – $75</td>
        </tr>
        <tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 12px; font-weight: bold;">Penthouses, Private Pool Villas & Event Stays</td>
            <td style="padding: 12px; color: #0284c7; font-weight: bold;">₦100,000 – ₦250,000</td>
            <td style="padding: 12px; color: #0284c7; font-weight: bold;">$100 – $250</td>
        </tr>
    </tbody>
</table>

<h2>3. Collection Timeline & Payment Methods</h2>
<ul>
    <li><strong>Payment Timing:</strong> Caution deposits must be paid prior to arrival (either during online checkout via Paystack or via verified instant bank transfer prior to key/passcode release).</li>
    <li><strong>No Key Without Deposit:</strong> In line with our standard operating procedures, property access codes and keys cannot be handed over until the caution deposit has cleared in our account.</li>
</ul>

<h2>4. Turnover Inspection & Verification Protocol</h2>
<p>Our commitment to transparency is absolute. We employ an audit-trailed inspection process:</p>
<ol>
    <li><strong>Pre-Arrival Inspection:</strong> Before your arrival, our housekeeping supervisor completes a timestamped checklist and photo record confirming the apartment's pristine condition.</li>
    <li><strong>Post-Checkout Inspection:</strong> Within two to four (2–4) hours following your departure, a supervisor reviews the inventory, electronics, furniture, walls, and linen.</li>
    <li><strong>Clean Clearance:</strong> If no damages, missing inventory, or policy violations are detected, your deposit is approved for immediate 100% refund release.</li>
</ol>

<h2>5. Itemized Deduction Schedule</h2>
<p>In the rare event of damage or policy violations, deductions are assessed transparently based on actual repair, replacement, or decontamination costs:</p>
<ul>
    <li><strong>Indoor Smoking / Smoke Odor:</strong> Fixed fee of <strong>₦50,000</strong> for industrial ozone decontamination, upholstery steam cleaning, and air filter replacements.</li>
    <li><strong>Lost Keys or Gate RFID Remotes:</strong> Fixed fee of <strong>₦15,000 per item</strong> for lock re-keying, hardware replacement, and gate sensor reprogramming.</li>
    <li><strong>Permanent Linen / Towel Staining:</strong> Stains from bleach, hair dye, wax, or red wine that cannot be laundered out will be charged at cost: ₦8,000 per luxury bath towel; ₦20,000 per bed sheet set.</li>
    <li><strong>Furniture / Wall / Decor Damage:</strong> Billed at actual verified repair or replacement cost plus a nominal 10% administrative coordination fee.</li>
    <li><strong>Unauthorized Late Check-out:</strong> ₦15,000 per hour for delays extending beyond the 11:00 AM check-out deadline.</li>
</ul>

<h2>6. Prompt Refund Release Window</h2>
<ul>
    <li><strong>Release Timeline:</strong> Approved refunds are initiated within <strong>twenty-four to forty-eight (24–48) hours</strong> following check-out inspection.</li>
    <li><strong>Payout Method:</strong> Funds are refunded directly to the original card or bank account used during payment. If paid by direct bank transfer, our finance team transfers the refund directly to your confirmed account name.</li>
</ul>

<h2>7. Photographic Proof & Dispute Guarantee</h2>
<p>We pride ourselves on honesty and fair dealing. <strong>No deduction is ever made without photographic proof and a detailed written explanation.</strong> In the unlikely event an issue is flagged, our guest relations supervisor will contact you directly with before/after photos and receipts before processing any deduction.</p>
HTML
            ],
            [
                'title' => 'Frequently Asked Questions',
                'slug' => 'faq',
                'meta_title' => "Frequently Asked Questions (FAQs) - {$brand}",
                'meta_description' => "Find instant answers to questions regarding booking, payments, 24/7 electricity, Wi-Fi, check-in logistics, and concierge services at {$brand}.",
                'content' => <<<HTML
<h2>1. Reservations & Booking</h2>

<h3>How do I make a reservation with {$brand}?</h3>
<p>Booking is effortless and instant. Explore our <a href="/apartments">Apartments Portfolio</a>, select your preferred location (e.g., Ikoyi, Victoria Island, Lekki Phase 1, or Abuja), choose your arrival and departure dates, select the number of guests, and proceed through our secure checkout. Once payment is confirmed, you will instantly receive your official booking confirmation and voucher via email and WhatsApp.</p>

<h3>Can I inspect the apartment before making a payment?</h3>
<p>To preserve the privacy, comfort, and security of our in-house guests, occupied apartments cannot be inspected in person. However, our website features 100% verified, high-resolution photographs, detailed floorplans, amenity lists, and video walkthroughs. For long-term corporate leases of 30+ nights, private viewings can be scheduled during vacant turnover windows by contacting our concierge desk.</p>

<h3>Are prices per person or per apartment?</h3>
<p>All nightly rates displayed on our portal are <strong>per apartment</strong>, up to the maximum registered guest capacity specified for that property (e.g., up to 2 guests for a 1-bedroom suite; up to 4 guests for a 2-bedroom suite).</p>

<h2>2. Payments & Financial Security</h2>

<h3>What payment methods do you accept?</h3>
<p>Through our secure payment gateway partner, <strong>Paystack</strong>, we accept all Nigerian and international Visa, Mastercard, and Verve debit and credit cards, Apple Pay, direct bank transfers (NIP), and USSD payments. For corporate organizations, direct electronic corporate wire transfers are accommodated upon invoice request.</p>

<h3>Is my credit card information secure?</h3>
<p>Yes, completely. Payments are processed through Paystack's PCI-DSS Level 1 certified infrastructure. Your card data is encrypted end-to-end; {$brand} never sees, stores, or processes raw credit or debit card details on our local servers.</p>

<h3>Are taxes and utility bills included in the nightly rate?</h3>
<p>Yes. Your nightly rate is inclusive of 24/7 continuous electricity, high-speed fiber internet, DSTV/Netflix streaming services, water supply, routine housekeeping, and estate service charges. Statutory 7.5% Nigerian VAT is itemized transparently before checkout.</p>

<h2>3. Arrival, Check-In & Departure</h2>

<h3>What are the check-in and check-out times?</h3>
<p>Standard check-in begins at <strong>2:00 PM (14:00 WAT)</strong>. Standard check-out must be completed by <strong>11:00 AM (11:00 WAT)</strong> to permit our professional turnover cleaning team to prepare the residence for incoming guests.</p>

<h3>Can I request an early check-in or late check-out?</h3>
<p>Early check-in (before 2:00 PM) and late check-out (after 11:00 AM) are subject to property availability and turnover schedules. While not guaranteed, requests should be made at least 24 hours in advance. Depending on timing, an hourly late fee or early check-in fee may apply.</p>

<h3>How does check-in work? Is it self-check-in?</h3>
<p>Most of our residences feature smart electronic door locks with secure digital passcodes, or front-desk keycard collection. Approximately 24 hours prior to arrival, our automated system delivers your personalized check-in access guide, gate clearance pass, Wi-Fi credentials, and building directions via WhatsApp and email.</p>

<h3>What documents do I need to present at check-in?</h3>
<p>Every primary guest must present a valid government-issued photo ID (NIMC NIN slip, International Passport, Driver's License, or Voter's Card) matching the name on the booking voucher. This is a mandatory requirement under Nigerian hotel register laws.</p>

<h2>4. Utilities, Power & Amenities</h2>

<h3>Is electricity really guaranteed 24/7?</h3>
<p>Yes, absolutely. We recognize that uninterrupted power is essential for luxury living and remote work. All {$brand} residences are backed by commercial dual-redundancy power systems: primary public grid power, commercial automated diesel generators, and centralized high-capacity inverter battery banks to ensure zero blackout intervals.</p>

<h3>How fast is the Wi-Fi? Can I work remotely or host Zoom meetings?</h3>
<p>Every apartment is connected to dedicated high-speed fiber-optic broadband delivering download and upload speeds between 50 Mbps and 150 Mbps with unlimited data. It is ideally suited for 4K video streaming, high-volume Zoom/Teams video conferencing, and heavy remote corporate workflows.</p>

<h3>Are kitchens fully equipped for cooking?</h3>
<p>Yes. All suites feature fully equipped kitchens outfitted with induction cooktops, built-in ovens, microwave ovens, large double-door refrigerators, electric kettles, blenders, cookware, frying pans, porcelain dinnerware, cutlery, and wine glasses.</p>

<h2>5. In-Residence Concierge & Room Service</h2>

<h3>Can I order food and laundry during my stay?</h3>
<p>Yes! Guests can use our in-residence digital Guest Portal or WhatsApp concierge desk to order fresh gourmet meals, continental breakfasts, local delicacies, laundry and dry-cleaning services, extra housekeeping, and airport pickup. Orders can be charged to your reservation folio or paid instantly via Paystack.</p>

<h3>Do you offer airport pickup from Lagos (MMIA) or Abuja (NAIA)?</h3>
<p>Yes. We operate private executive airport chauffeur pickups in air-conditioned luxury sedans and executive SUVs. You can add airport pickup during online checkout or request it through our concierge team up to 12 hours prior to flight arrival.</p>

<h2>6. Cancellations & Caution Deposits</h2>

<h3>When do I receive my refundable caution deposit back?</h3>
<p>Following check-out, our housekeeping supervisor conducts a routine turnover inspection. Once cleared, your caution deposit is processed for release within <strong>24 to 48 hours</strong> directly back to your funding bank account or card.</p>

<h3>What happens if I need to cancel or change my booking?</h3>
<p>You can cancel or reschedule directly from your <a href="/login">Guest Dashboard</a>. Cancellations completed more than 72 hours before check-in receive a 100% full refund (less nominal payment processing fees). Please review our complete <a href="/cancellation-policy">Cancellation & Refund Policy</a> for detailed timeframes and stay credit options.</p>
HTML
            ],
            [
                'title' => 'Property Owner & Host Partnership Terms',
                'slug' => 'owner-partnership-terms',
                'meta_title' => "Property Owner & Landlord Partnership Terms - {$brand}",
                'meta_description' => 'Partner with BookTala to convert your residential property into a high-yielding luxury short-let asset with 24/7 asset care and transparent payouts.',
                'content' => <<<HTML
<h2>1. Partnering with BookTala</h2>
<p><strong>{$brand}</strong>, a luxury residential hospitality and asset management division of <strong>{$owner}</strong>, partners with discerning property owners, real estate investors, and developers across premier locations in Lagos (Ikoyi, Victoria Island, Lekki Phase 1, Eko Atlantic, Ikeja GRA) and Abuja (Maitama, Wuse 2, Jabi, Guzape).</p>
<p>We transform residential real estate into high-performing, professionally managed hospitality assets that outperform traditional 1-year annual tenancies by 40% to 120% in net rental yield, while maintaining your property in pristine condition.</p>

<h2>2. Our Management Models</h2>
<p>We offer two flexible partnership structures tailored to your investment appetite:</p>
<ul>
    <li><strong>Model A: Performance Commission Revenue-Share (80/20 or 85/15)</strong><br>
    The owner retains 80% to 85% of net accommodation revenue, while {$brand} retains a 15% to 20% management commission. Revenue is maximized through our multi-channel distribution network (Direct BookTala Portal, Airbnb Superhost status, Booking.com Premier Partner, Channex OTA sync, and corporate corporate accounts).</li>
    <li><strong>Model B: Guaranteed Fixed Monthly Lease</strong><br>
    For owners who prefer predictable, passive income with zero vacancy risk, {$brand} leases the property on a guaranteed 1-year to 3-year commercial residential lease with guaranteed monthly or quarterly upfront disbursements.</li>
</ul>

<h2>3. Property Onboarding Standards & Specifications</h2>
<p>To preserve our brand reputation and ensure five-star guest satisfaction, properties onboarded onto the {$brand} collection must satisfy our luxury baseline criteria:</p>
<ul>
    <li><strong>Prime Location:</strong> Secure gated estates or upscale residential towers with round-the-clock armed gate security.</li>
    <li><strong>24/7 Power Infrastructure:</strong> Centralized estate generator backup, dedicated soundproof diesel generators, or high-capacity inverter battery systems.</li>
    <li><strong>High-Standard Interior Furnishings:</strong> Luxury orthopaedic mattresses, bespoke furniture, blackout curtains, smart 4K televisions in bedrooms and living rooms, and fully fitted modern kitchens.</li>
    <li><strong>Smart Security & Connectivity:</strong> Electronic smart door locks, high-speed fiber-optic internet connection, and video intercom hardware.</li>
</ul>

<h2>4. Professional Operations & Asset Maintenance</h2>
<p>Unlike traditional residential tenants who subject properties to unchecked wear and tear, {$brand} treats your property as a commercial hospitality investment:</p>
<ul>
    <li><strong>Turnover Housekeeping:</strong> Every apartment is deep-cleaned, sanitized, and restocked by professional housekeeping staff after every single stay.</li>
    <li><strong>Preventative Maintenance Planner:</strong> Scheduled bi-monthly air conditioner servicing, plumbing inspections, generator run-hour tracking, and routine touch-up painting.</li>
    <li><strong>Zero Tenant Defaults:</strong> No risk of recalcitrant tenants refusing to pay rent or overstaying annual leases. Guests pay 100% upfront before keys are released.</li>
</ul>

<h2>5. Owner Personal Stay Allotments</h2>
<p>We recognize that property owners often enjoy staying in their own luxury apartments when visiting Lagos or Abuja. Under our Revenue-Share model, property owners are entitled to up to <strong>thirty (30) complimentary personal stay days per calendar year</strong> for personal, family, or executive use. You can easily block out your personal dates in advance through the online <a href="/login">Owner Portal</a>.</p>

<h2>6. Real-Time Owner Portal & Monthly Remittances</h2>
<ul>
    <li><strong>Dedicated Owner Dashboard:</strong> Owners receive private 24/7 access to our Owner Portal to view real-time calendar reservations, occupancy rates, average daily rates (ADR), and gross earnings.</li>
    <li><strong>Monthly Itemized Statements:</strong> On the first business day of each calendar month, an itemized statement detailing all completed reservations, OTA commissions, cleaning costs, and net proceeds is automatically generated.</li>
    <li><strong>Prompt Direct Bank Payouts:</strong> Net rental proceeds are disbursed via direct electronic bank transfer directly to your designated corporate or personal bank account on or before the <strong>5th day of every calendar month</strong>.</li>
</ul>

<h2>7. Guest Vetting, KYC & Damage Protection Guarantee</h2>
<ul>
    <li><strong>Strict KYC Vetting:</strong> All incoming guests must undergo mandatory identity verification (NIMC NIN, International Passport, or Driver's License) before receiving access codes.</li>
    <li><strong>Caution Deposit Escrow:</strong> We collect and hold refundable caution deposits from every guest to rectify any minor damages or breakages.</li>
    <li><strong>Comprehensive Asset Care:</strong> In the unlikely event of guest negligence exceeding the caution deposit, {$brand}'s host protection protocol ensures prompt repair or replacement without disrupting owner yield.</li>
</ul>

<h2>8. How to Onboard Your Property</h2>
<p>Ready to maximize your property's earning potential? Contact our Property Acquisition & Onboarding Team today:</p>
<p><strong>Property Partnership Desk — {$owner}</strong><br>
Email: <a href="mailto:{$email}">{$email}</a><br>
Direct Phone / WhatsApp: <a href="tel:{$phone}">{$phone}</a><br>
Address: {$address}</p>
HTML
            ],
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'meta_title' => "About Us - {$brand} Luxury Stays, Travels & Better Living",
                'meta_description' => 'Discover the BookTala story, our mission to redefine short-let hospitality across Lagos and Abuja, and our commitment to luxury serviced living.',
                'content' => <<<HTML
<h2>Stays, Travels and Better Living, Made Simple</h2>
<p><strong>{$brand}</strong> is Nigeria's premier short-let hospitality and serviced residence platform, proudly owned and operated by <strong>{$owner}</strong>. Headquartered in Lagos, {$brand} was born out of a clear, uncompromising mission: to give modern travelers, remote professionals, executives, and vacationing families the space, privacy, and warmth of a luxury home, backed by the rigorous service standards, security, and convenience of a five-star hotel.</p>

<h2>The Meaning Behind BookTala</h2>
<p>Our brand name reflects our dual commitment to seamless travel logistics and elevated lifestyle living:</p>
<ul>
    <li><strong>BOOK:</strong> Instant, frictionless online reservation and secure payment processing. Transparent rates, zero hidden charges, and immediate digital confirmation vouchers delivered in seconds.</li>
    <li><strong>TALA:</strong> An acronym for <em>Travel, Accommodation, Lifestyle, and Access</em>. We provide hand-picked residences in prime, highly sought-after neighborhoods with premier amenities, uninterrupted 24/7 power, high-speed fiber connectivity, and dedicated concierge care.</li>
</ul>

<h2>Our Core Pillars</h2>
<ul>
    <li><strong>Prime Strategic Locations:</strong> From the tree-lined diplomatic boulevards of Ikoyi and the vibrant financial core of Victoria Island, to the serene coastal luxury of Lekki Phase 1 and the executive districts of Maitama and Wuse 2 in Abuja, our residences place you right where you need to be.</li>
    <li><strong>Uninterrupted 24/7 Power Assurance:</strong> We understand that reliable electricity is non-negotiable. Every {$brand} residence features robust multi-tier power redundancy including commercial industrial soundproof generators and automated inverter systems.</li>
    <li><strong>Enterprise-Grade Connectivity:</strong> Dedicated high-speed fiber-optic Wi-Fi in every residence ensures seamless remote productivity, high-definition Zoom video conferencing, and unlimited 4K streaming.</li>
    <li><strong>Hospitality-Grade Housekeeping:</strong> Professional sanitization, five-star luxury linens, plush bath towels, and routine turnover inspections ensure every arrival feels like walking into a brand new suite.</li>
    <li><strong>In-Residence Concierge:</strong> Gourmet room service delivery, private airport chauffeur pickups, laundry and dry cleaning, and bespoke resident support available at the tap of a button.</li>
</ul>

<h2>Leadership & Corporate Governance</h2>
<p>{$brand} is backed by the institutional real estate and asset management expertise of <strong>{$owner}</strong>. Our multidisciplinary leadership team combines decades of international hospitality management, prime property development, corporate finance, and cutting-edge software engineering to build West Africa's most trusted short-let hospitality network.</p>

<h2>Experience Better Living</h2>
<p>Whether you are planning a weekend staycation, an extended corporate assignment, or an international family holiday, we invite you to experience the {$brand} difference.</p>
<p>Browse our <a href="/apartments">Apartments Portfolio</a> or get in touch with our concierge team at <a href="mailto:{$email}">{$email}</a>.</p>
HTML
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact-us',
                'meta_title' => "Contact Us - {$brand} 24/7 Guest Concierge & Support",
                'meta_description' => 'Get in touch with the BookTala team for reservations, corporate leases, property management partnerships, or 24/7 guest concierge support.',
                'content' => <<<HTML
<h2>Get in Touch with BookTala</h2>
<p>Have questions about an upcoming reservation, looking for corporate accommodation, or interested in listing your property in our collection? Our dedicated guest concierge and reservations team is available around the clock to assist you.</p>

<h2>Guest Concierge & Reservation Support</h2>
<p>Our front desk and concierge personnel are on standby 24 hours a day, 7 days a week:</p>
<ul>
    <li><strong>Direct Telephone Hotline:</strong> <a href="tel:{$phone}">{$phone}</a></li>
    <li><strong>WhatsApp Concierge:</strong> <a href="https://wa.me/{$phone}">Chat on WhatsApp</a></li>
    <li><strong>General & Reservation Inquiries:</strong> <a href="mailto:{$email}">{$email}</a></li>
    <li><strong>Corporate Accounts & Long Stays:</strong> <a href="mailto:{$email}">corporate@booktala.com</a></li>
    <li><strong>Property Owner & Host Partnerships:</strong> <a href="mailto:{$email}">partners@booktala.com</a></li>
</ul>

<h2>Head Office & Operational Base</h2>
<p><strong>{$owner} (Operating {$brand})</strong><br>
{$address}<br>
Operating Hours: Monday – Sunday, 24 Hours Daily</p>

<h2>Send Us an Online Message</h2>
<p>You can also utilize our interactive <a href="/contact">Online Contact Form</a> to send an instant message. A dedicated hospitality specialist will review your request and get back to you within two (2) hours.</p>
HTML
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                $page + ['is_published' => true]
            );
        }
    }
}
