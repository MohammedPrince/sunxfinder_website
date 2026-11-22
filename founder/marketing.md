# SunXFinder: Marketing Plan

Written 2026-11-19. Builds on [docs/marketing-plan.md](../docs/marketing-plan.md) (phases, companies first) and [docs/facebook-posts.md](../docs/facebook-posts.md) (the first 15 posts). This file adds positioning, channels, a dated 30-day launch campaign, hooks, social media detail, budget and the numbers to watch.

## Read this first: what the plan is based on

The founder pack has not run yet: there is no buyer panel, competitor map, offer, pricing or CFO model in `founder/`. So:

| Normally comes from | Used here instead | Confidence |
| --- | --- | --- |
| `founder/panel/results.md` (who buys, objections) | The objections the existing Facebook posts already answer, plus public reporting on Sudan's power crisis | **Hypothesis.** Validate with `/founder-consumer --quick` |
| `founder/competitors.md` | The real alternatives a Sudanese buyer uses today: a company's Facebook page, a shop in the market, a friend's installer, a generator | **Hypothesis** |
| `founder/pricing.md` | Customers pay nothing. Company prices are the starting guesses in [company-monetization-ideas.md](../docs/company-monetization-ideas.md) | Guesses |
| `founder/numbers.json` | None. The budget is a fixed monthly amount, not derived from customer value | See [Budget and numbers](#5-budget-and-numbers) |

Facts from public sources:

- Demand for solar has surged since early 2024 as the grid failed. Khartoum has the highest home solar adoption (over 7%); the east and centre are around 3%. A 590 W panel cost SDG 218,000 to 255,000 in Khartoum and Port Sudan in late 2025 ([UNDP](https://www.undp.org/sudan/press-releases/solar-power-could-keep-sudans-farms-and-businesses-running-grid-collapses-urgent-investment-and-reform-needed-secure-its)).
- A typical 10 kW household system costs close to $5,000, and high up-front cost keeps many people out ([AP via ABC News](https://abcnews.com/International/wireStory/sudans-war-driven-blackouts-push-residents-solar-power-136235978)).
- Solar systems were fully exempted from customs duties in August 2026 ([Sudan Tribune](https://sudantribune.com/?p=317042), check the date).
- About 6 million active social media identities in Sudan as of October 2025 ([DataReportal 2026](https://datareportal.com/reports/digital-2026-sudan)). Facebook dominates; one secondary source gives it about 74% of social media share ([eMediaMonitor](https://www.emediamonitor.net/en/country-information/north-africa/media-monitoring-sudan), source unclear).

SunXFinder has two audiences and markets to them in a fixed order: **companies first, customers second** (see [docs/marketing-plan.md](../docs/marketing-plan.md)). This file covers both, with most of the detail on customers, because that is where social media does the work.

---

## 1. Who we are talking to (hypotheses to test)

### Customers

| Segment | Why they might buy | Likely objection | Priority |
| --- | --- | --- | --- |
| **A. Homeowners in Khartoum and Port Sudan** running a generator or living with long cuts | Fuel cost, noise, fuel shortages, a fridge that keeps spoiling food | "I don't know what size I need, and I'm afraid of being cheated" | **1st: launch audience** |
| **B. Small shop owners** (grocery, pharmacy, phone shop, tailor) | Lost sales during cuts, fridge stock, opening hours | "I can't afford to stop and shop around" | 2nd |
| **C. Sudanese abroad paying for a family system** (Gulf, Egypt, UK) | They pay but can't check the installer in person | "How do I know the company is real and did the work?" | 3rd: test it, it may turn out to be the richest segment |
| D. Farms and water pumps | Diesel for irrigation | Large, technical, slow decisions | Later |

### Objections every message must answer

These come from the existing posts and the news reporting, not from a panel:

1. **"I don't know what system I need."** Answer: the free calculator, and "send us your appliances".
2. **"Panels and batteries here are fake or weak."** Answer: trust content, and written quotes with brand and model.
3. **"I don't know which company to trust."** Answer: compare several written offers, and ratings from real finished projects.
4. **"It's too expensive."** Answer: compare against your fuel bill, start small and expand later. No promised savings.
5. **"I'll get calls and spam if I give my number."** Answer: your number only goes to the companies you choose.
6. **"Another app that doesn't work."** Answer: only launch where 20 or more companies already reply.

### Companies

Solar installers and shops in the launch city. Their objections: "Will it bring real customers?", "Will I have to pay later?" and "I don't have time to make a profile." The answer is already in the plan: it's free, we build your page for you, here are real request numbers, and the founding-company offer.

---

## 2. Positioning

Three versions:

**Option 1: Trust (recommended)**
> For families in Sudan who are tired of power cuts and generator fuel but afraid of paying for a weak or fake system, SunXFinder is the free solar marketplace where you size your system first, then compare written offers from several companies, unlike calling one company from Facebook and hoping.

**Option 2: Knowing what you need**
> For people who want solar but don't know where to start, SunXFinder is the free calculator and company finder that tells you what system you need before anyone tries to sell you one.

**Option 3: Saving money**
> For households paying for generator fuel every day, SunXFinder is the fastest way to get competing solar prices, so you pay a fair price once instead of paying for fuel forever.

**Recommendation: Option 1.** Objections 1 to 3 (size, fakes, trust) come up again and again in the existing posts and DMs, and the alternatives can't answer them. One company's Facebook page can't offer a comparison. A market shop can't offer a written comparison. A friend's installer offers one opinion. Comparison is the gap SunXFinder can own. Option 3 leads with price, which pulls in buyers who will drop out at a $5,000 quote, and it invites savings claims we can't prove. Option 2 is the best **entry hook** ("calculate for free"), so use it in ads, but sell trust.

**One-line promise (Arabic first):**
- احسب. قارن. اختار براحتك.
- Calculate. Compare. Choose with confidence.

The tagline "Find. Compare. Connect. / اعثر. قارن. تواصل." stays. The line above is for ads.

**How not to sound like a solar company:** never post panel prices, "best prices in town" or product photos with prices. That is what every installer page does. SunXFinder is the neutral helper standing next to the buyer.

---

## 3. Channels

### Start with these three

| Channel | Why it fits | Rough cost | How you know it worked |
| --- | --- | --- | --- |
| **1. Facebook page + Facebook groups** | Most Sudanese social use is on Facebook. Neighbourhood, city and solar groups are where people already ask "who installed yours?" | Free, plus $3 to $10 a day in boosts | Messages a week, calculator clicks from Facebook, comments asking for a calculation |
| **2. WhatsApp (Business number, Status, Channel, broadcast list)** | Sudan runs on WhatsApp. It's where quotes and photos get passed to family, including people abroad | Free | People who join the waitlist, forwards, replies |
| **3. Companies sharing their own SunXFinder link** | Each company has followers you don't have, and its customers already trust it | Free. A ready-made image and text per company | Installs and requests from company links (the `/companies/slug` deep links) |

### Add in week 3 or later, once the three above are working

| Channel | Why | Cost | Signal |
| --- | --- | --- | --- |
| **TikTok** | Short "how much solar for X" videos travel among young people who decide for their families | Free organic | Views over 3 s, profile visits, calculator link taps |
| **Instagram Reels** | Re-post the TikTok videos. Diaspora and younger urban buyers are here | Free, cross-post | Same as TikTok |
| **Local creators / engineers** (paid partnership) | One respected Sudanese solar engineer explaining "how to compare 3 quotes" earns more trust than ten ads | $30 to $100 a video, **disclosed as paid** | Clicks on their tracked link, requests in the following week |

### Not doing yet, and why

- **Google Search ads:** search volume in Sudan is small and payments are hard. Revisit in month 4.
- **Paid installs ("Install now" ads):** an install without a company nearby is wasted money. Advertise the calculator, not the app.
- **Billboards, radio, flyers:** expensive and hard to measure. A printed sign in the shops of listed companies is the exception: it's cheap and placed exactly where buyers already are.
- **Covering the whole country:** only target cities where companies exist.

---

## 4. The 30-day launch campaign

**Launch day (L) = Sunday 17 January 2027**, the start of the Sudanese working week. It's a target, not a promise: launch only when the go-live checklist in [docs/marketing-plan.md](../docs/marketing-plan.md#phase-2-soft-launch-the-app-weeks-8-to-12) is all ticked (20+ companies, 10+ replied to a test request, full flow works, Google approved). If it slips, move the whole calendar; the days are relative.

The campaign ends **Tuesday 2 February 2027**, about a week before Ramadan (expected to start around 8 February 2027, depending on the moon sighting). Ramadan offers and the hot season (April to June) are the next campaigns.

### Launch-week offers (real, with an end date)

| For | Offer | Ends |
| --- | --- | --- |
| Customers | **"Send us any quote you already got, and we'll check it for free."** A human reviews whether the quote lists sizes, brands, models, warranty and protection. Uses the checklist in post 5. | **Sunday 24 January 2027 (L+7)** |
| Customers | Every request in the launch city gets at least one offer within 24 hours. If a company doesn't reply, you call them. (This is a promise you keep, not a discount.) | Ongoing |
| Companies | **Founding company** badge, a lifetime discount on future paid plans, and a free home-slider week | **Tuesday 2 February 2027 (L+16), or when 30 companies have joined**, whichever comes first |

Customers pay nothing, so there is no price discount for them. The quote check is the launch offer because it answers objection 2 (fakes) and objection 3 (trust).

### Daily calendar

FB = Facebook page, GR = Facebook groups, WA = WhatsApp, TT = TikTok, IG = Instagram Reels. "Post n" means the post of that number in [facebook-posts.md](../docs/facebook-posts.md). "Hook n" means one of the [ten hooks](#ten-hooks) below.

| Date | Day | Channel | What goes out | Message / objection |
| --- | --- | --- | --- | --- |
| Sun 3 Jan | L-14 | FB, WA | Open the **waitlist**: "Leave your number and city, and we'll tell you the day the app opens in your city." WhatsApp broadcast list "SunXFinder launch". | Waitlist |
| Mon 4 Jan | L-13 | FB, GR | Post 2 (start with your load). Answer 5 questions in city groups (no links unless asked). | Obj. 1 |
| Tue 5 Jan | L-12 | FB, WA Status | Post 3 (free calculation). Reply to every comment with the calculator result. | Obj. 1 |
| Wed 6 Jan | L-11 | Companies | Send every listed company its **sharing kit**: their profile link, a square image "Find us on SunXFinder", and Arabic text. Ask them to post on launch day, not before. | — |
| Thu 7 Jan | L-10 | FB, TT | Post 10 (fake panels), plus a 30 s video reading a panel label. **Hook 2.** | Obj. 2 |
| Fri 8 Jan | L-9 | FB | Behind the scenes: "This week we visited 3 solar companies in Khartoum." Real photos, with permission. | Trust |
| Sat 9 Jan | L-8 | FB, GR | Post 12 (solar vs generator), plus "comment with your monthly fuel cost". **Hook 4.** Boost $5/day for 3 days in the launch city. | Obj. 4 |
| Sun 10 Jan | L-7 | FB, WA, TT | **Teaser:** "In 7 days: calculate, compare offers, choose with confidence. In one app." Screen recording of the calculator. | Waitlist |
| Mon 11 Jan | L-6 | FB | Post 5 (7 questions before you pay). **Hook 3.** | Obj. 3 |
| Tue 12 Jan | L-5 | FB, IG | "Meet the companies": a carousel of 6 founding companies (logo + city), each tagged. | Obj. 6 |
| Wed 13 Jan | L-4 | TT, IG, FB | Video: "What a fridge + 4 fans + 6 lights needs", with the calculator on screen. **Hook 1.** | Obj. 1 |
| Thu 14 Jan | L-3 | FB, WA | Privacy post: "Your number goes only to the companies you choose. No spam calls." **Hook 6.** | Obj. 5 |
| Fri 15 Jan | L-2 | WA | Message to the waitlist: "Sunday. Here's what to have ready: a list of your appliances." | Waitlist |
| Sat 16 Jan | L-1 | FB, WA Status | Countdown image: "Tomorrow". Check the Play Store link, the deep links and every company's phone. | — |
| **Sun 17 Jan** | **L** | **All** | **Launch.** 09:00 pinned post plus a 45 s video (calculator → request → 3 offers arrive). 10:00 WhatsApp to the waitlist with the Play link. 11:00 companies post their kits. All day: reply to every comment within an hour. **Hook 5.** | Positioning |
| Mon 18 Jan | L+1 | FB, TT | **Launch offer:** "Got a quote already? Send it, we'll check it free until Sunday." **Hook 7.** | Obj. 2, 3 |
| Tue 19 Jan | L+2 | FB | Live (20 min, evening): "Ask an engineer anything about solar." Invite a listed company's engineer. | Obj. 1 to 3 |
| Wed 20 Jan | L+3 | GR, WA | Share a real, anonymised **quote check**: what was missing (no brand, no earthing). With the customer's permission. | Obj. 2 |
| Thu 21 Jan | L+4 | FB, IG | Post 15 (compare before you choose). Boost $8/day for 4 days: calculator link, launch city, ages 25 to 55. | Obj. 3 |
| Fri 22 Jan | L+5 | FB | Behind the scenes: "Our first week, in numbers." **Real numbers only** (e.g. "112 calculations, 31 requests, 74 offers"). | Proof |
| Sat 23 Jan | L+6 | TT, IG | Video: "3 quotes for the same house, 3 different prices. Here's why." Anonymised real offers. **Hook 8.** | Obj. 3, 4 |
| Sun 24 Jan | L+7 | FB, WA | **Last day of the free quote check.** "Ends tonight." | Urgency |
| Mon 25 Jan | L+8 | FB | Post 4 (lithium or gel). | Obj. 4 |
| Tue 26 Jan | L+9 | FB, WA | **Diaspora test:** "Paying for your family's solar from abroad? Compare written offers and follow the project in the app." Boost $5/day for 4 days to Sudanese abroad in Saudi Arabia, UAE, Qatar and Egypt. **Hook 9.** | Segment C |
| Wed 27 Jan | L+10 | TT, IG | Video: post 6 (heat and dust), with a cleaning demo. | Education |
| Thu 28 Jan | L+11 | FB, GR | Company spotlight: a real finished project from a listed company. Tagged, with permission. | Obj. 3 |
| Fri 29 Jan | L+12 | FB | Post 11 (AC on solar). | Obj. 1 |
| Sat 30 Jan | L+13 | FB, TT | "Start small, grow later": a system you can expand. **Hook 10.** | Obj. 4 |
| Sun 31 Jan | L+14 | Companies | Message companies: "Founding offer closes Tuesday." Send each one its first two weeks of numbers. | Companies |
| Mon 1 Feb | L+15 | FB, WA | Shop owners: "Your shop shouldn't close when the power does." | Segment B |
| Tue 2 Feb | L+16 | FB | Wrap-up with real numbers, and thanks to the companies. **Founding offer closes.** Plan the Ramadan campaign. | Proof |

### When there is nothing new to say

Use these, in rotation:

- **Behind the scenes:** visiting a company, building a profile, fixing a bug a customer reported.
- **Quote of the week:** an anonymised quote check (with permission), showing what was good and what was missing.
- **Myth or fact:** "More panels always means more power?" Answer it in a comment poll.
- **Answering a question from the comments** as a short video.
- **A company's project**, tagged, with the company's permission.
- **One of the 15 education posts again**, as a video instead of text.

---

## Ten hooks

Each hook answers one objection. Arabic first, written for its format. Nothing in them promises savings, quotes a price or claims customers we don't have yet.

| # | Format | Arabic | English | Answers |
| --- | --- | --- | --- | --- |
| 1 | Video, 3 s opener | ثلاجة و4 مراوح… محتاج كم لوح؟ | A fridge and 4 fans… how many panels? | Obj. 1 (size) |
| 2 | Video, 3 s opener | قبل ما تشتري لوح، اقلبه وشوف الورقة دي | Before you buy a panel, flip it and read this label | Obj. 2 (fakes) |
| 3 | Post headline | 7 أسئلة لو الشركة ما جاوبتها، ما تدفع | 7 questions: if the company can't answer, don't pay | Obj. 3 (trust) |
| 4 | Post headline | كم بتصرف على الجازولين في الشهر؟ اكتبها في التعليقات | How much do you spend on generator fuel a month? Tell us below | Obj. 4 (cost) |
| 5 | Ad headline | احسب منظومتك مجاناً، وقارن عروض أكتر من شركة | Calculate your system free, then compare offers from several companies | Positioning |
| 6 | Image text | رقمك بيمشي بس للشركة العايزها إنت | Your number only goes to the company you choose | Obj. 5 (spam) |
| 7 | Video, 3 s opener | وصلك عرض سعر؟ رسلو لينا ونراجعو ليك مجاناً | Got a quote? Send it to us and we'll check it free | Obj. 2, 3 |
| 8 | Video, 3 s opener | نفس البيت، 3 عروض، 3 أسعار مختلفة. ليه؟ | Same house, 3 quotes, 3 different prices. Why? | Obj. 3, 4 |
| 9 | Ad headline (diaspora) | بتدفع لمنظومة أهلك من برّه؟ قارن العروض وتابع الشغل من التطبيق | Paying for your family's solar from abroad? Compare offers and follow the job in the app | Segment C |
| 10 | Shop-window / company sign | ابدأ بالمهم، وكبّر منظومتك بعدين | Start with what matters, grow your system later | Obj. 4 |

Test the hooks against each other: boost two versions of the same post for 2 days at $3/day each, keep the one with more messages per dollar, and drop the other.

---

## Social media in detail

### Profiles (same name, logo and link everywhere)

| Platform | Handle (check it's free) | Bio, Arabic first | Link |
| --- | --- | --- | --- |
| Facebook | SunXFinder | احسب منظومتك مجاناً وقارن عروض شركات الطاقة الشمسية في السودان ☀️ Calculate free, compare solar offers in Sudan | sunxfinder.com |
| Instagram | @sunxfinder | Same, shorter | Link to the calculator, not the home page |
| TikTok | @sunxfinder | احسب. قارن. اختار براحتك ☀️ | Calculator |
| WhatsApp Business | SunXFinder | Catalogue entries: "Free calculation", "Free quote check", "Find companies" | Quick replies set up (below) |

### Content pillars and the weekly rhythm (after launch)

| Pillar | Share | Example | Where |
| --- | --- | --- | --- |
| **Educate** | 40% | Load, batteries, inverters (محول), heat and dust | FB, TT, IG |
| **Calculate** | 20% | "Send your appliances", answering live in comments | FB, WA |
| **Trust** | 20% | Fake panels, quote checks, questions before you pay | FB, GR, TT |
| **Companies and projects** | 15% | Tagged real projects, company spotlights | FB, IG |
| **App / news** | 5% | New feature, new city | All |

| Day | Facebook | TikTok / Reels | WhatsApp |
| --- | --- | --- | --- |
| Sunday | Education post | — | Status: the post's image |
| Monday | — | Education video | — |
| Tuesday | Free calculation | — | Status |
| Wednesday | — | Trust video | — |
| Thursday | Trust post | — | Channel: tip of the week |
| Saturday | Company project | Re-post the best video | Status |

At least four Facebook posts and two videos a week. Re-use one idea in three formats (post, video, status) instead of writing three ideas.

### Video formats that fit this product

1. **"Calculator on screen"** (30 to 45 s): type a real home's appliances and show the result. Hook 1.
2. **"Read the label"** (20 to 30 s): a hand turns a panel over, with arrows on the label. Hook 2.
3. **"Three quotes"** (45 to 60 s): three anonymised offers side by side, with what's different. Hook 8.
4. **"Engineer answers"** (30 s): one question from the comments, answered by a listed company's engineer (tag them).
5. **"Before you pay"** (30 s): one question from the checklist per video. Seven videos from one post.

Rules for video: vertical 9:16; Arabic captions burned in (most people watch on mute); the hook spoken and written in the first 3 seconds; logo in the corner, not an intro; end with "the link is in the bio" or "message us your appliances". Sudanese dialect, as in the existing posts.

### Facebook groups

- Join 10 to 15 groups: the launch city's neighbourhood groups, solar and electrical groups, and Sudanese-abroad groups (for segment C).
- Answer questions as a person from the SunXFinder page, helpfully. Share a link only when someone asks "where do I calculate?" or "who installs in my area?".
- No more than one group post a week per group. Read each group's rules; many ban promotion.

### Comment and DM replies (save as WhatsApp quick replies)

| Situation | Reply (Arabic; translate as needed) |
| --- | --- |
| "How much does it cost?" | الأسعار بتختلف حسب أجهزتك والماركة. رسل لينا أجهزتك وكم ساعة تحتاجها في الليل، ونحسب ليك الحجم، وبعدها الشركات بترسل ليك عروض تقارن بينها. |
| "Which company is best?" | ما بنختار ليك شركة، بنخليك تقارن. اطلب عرض من التطبيق وبتجيك عروض مكتوبة من أكتر من شركة، وشوف تقييمات المشاريع المكتملة. |
| A complaint about a company | We're sorry. Move it to a private message, take the details, and follow the complaints flow in the app. Never argue in public. |
| A company asks to advertise | Thank them, explain listing is free, and send the company pitch from the plan. |

### Paid social settings (Meta)

- **Objective:** Messages (WhatsApp/Messenger) or Traffic to the calculator. Not app installs, not likes.
- **Place:** the launch city plus a 20 to 30 km radius only. Segment C: Sudanese abroad in Saudi Arabia, UAE, Qatar and Egypt (interest "Sudan" + language Arabic). Test this small.
- **Creative:** boost organic posts that already got messages (posts 3, 8, 10 and 12 per the existing plan). Don't make new ads from scratch.
- **Ad account:** paying for Meta ads from Sudan can be difficult. Check whether you can pay with a card from a family member or partner abroad, and keep receipts.
- **Labels:** paid partnerships use Meta's "Paid partnership" tag. Ads inside the app carry the **Sponsored (ممول)** label.

### Creator partnerships (from week 3)

- Choose Sudanese electrical or solar engineers who already teach. Avoid general influencers.
- The brief: "How to compare three solar quotes, using SunXFinder". They give their honest view. You don't write their opinion.
- **Disclose every paid video** ("إعلان مدفوع / paid partnership"). Never ask a creator to pretend they are a customer.

### What we never post

- Fake reviews, follower counts, testimonials or "as seen on".
- Made-up customer numbers. Only real counts from the admin panel, rounded down.
- Savings promises ("save 80%", "pays for itself in 2 years"). Say "work out your own fuel cost" instead.
- Before/after photos that aren't ours or aren't real.
- Panel quotes from the future `/founder-consumer` research. It is simulated research, never customer quotes.
- Customer photos, names or quotes without written permission.

---

## 5. Budget and numbers

### Budget split (first 30 days, about $250)

The total follows the plan's ranges ($50–100/month before launch, $150–300 after). It isn't derived from customer value, because there's no CFO model yet.

| Item | Amount | Notes |
| --- | --- | --- |
| Facebook/Instagram boosts, launch city | $150 | Mostly L-8 to L+7 |
| Diaspora test (segment C) | $20 | 4 days at $5. Kill it if it costs more than 2× per message than the launch city |
| Hook A/B tests | $25 | Two hooks × 2 days × $3 |
| Creator video (1, disclosed) | $50 | Optional, from L+7 |
| Designer for the launch image set and company kits | $0–50 | Or Canva templates |
| **Total** | **$245–295** | |

Spend more time than money: calls to companies and replies in comments are worth more than ads at this stage.

### The most you can pay to win a customer

**This can't be calculated yet.** Customers pay nothing; companies will pay later. The sum, once the CFO and pricing steps exist:

```
Value of one quote request = (company revenue per month) ÷ (quote requests per month)
Max cost per request         = value of one request × target share for marketing (e.g. 30%)
Max cost per customer        = max cost per request × requests per customer (usually 1, solar is bought once)
```

Example using **only the guesses** in [company-monetization-ideas.md](../docs/company-monetization-ideas.md): 20 companies on Silver at $25 is $500/month. With 80 requests a month, each request is worth about $6.25. At 30% for marketing, that's about **$1.90 per quote request**. Until companies pay, revenue is $0, so treat this as a ceiling to aim under, not a budget.

For now, the working number is **cost per quote request = ad spend ÷ requests from ads**. Aim to keep it under $3 during launch.

### Three numbers to watch weekly

| Number | Where to read it | Healthy | Change something if… |
| --- | --- | --- | --- |
| **1. Quote requests per week** (launch city) | Admin panel | 20+ by week 2 | Under 10 for two weeks in a row: change the hook and the boosted post, and push companies to share their links |
| **2. Requests with at least one offer within 24 h** | Admin panel | 90%+ | Under 80%: stop all ads, call companies, and don't add customers you can't serve |
| **3. Cost per quote request from ads** | Meta spend ÷ requests that came from Meta | Under $3 | Over $5 for a week: pause the ad, boost a different post, narrow the area |

Also watch, without acting weekly: calculator uses, waitlist size, messages per post, push opt-outs (a sign of too many sponsored pushes), and companies who reply.

Number 2 matters most. Ads that bring customers into an app where nobody replies do more harm than no ads.

---

## Rules

- Every paid placement inside the app carries **Sponsored (ممول)**. Every paid creator post is disclosed.
- Customer photos, quotes and names only with written permission.
- No savings or health claims. Typical values are labelled typical, with the final design always coming from a company that visits the site.
- Use محول for inverter in Arabic, not عاكس.
- Respect the sponsored-push opt-out and the 1–2 pushes per customer per week limit.
- Check Meta's advertising policies and Sudan's consumer rules for claims. This is not legal advice.

---

## Decisions for you

1. Launch city: Khartoum, Port Sudan or both? (Khartoum has the highest adoption, Port Sudan has the import route.)
2. Is 17 January 2027 realistic, given the go-live checklist?
3. Who replies to comments and messages on launch day, and within what time?
4. Will you test the diaspora segment (C) in this campaign or leave it for later?
5. Monthly ad budget: is $250 right?

## Next

1. `/founder-consumer --quick`: test segments A–C and the six objections above on a simulated panel, then revise this file.
2. `/founder-competitors`: map the real alternatives (installers' pages, market shops, any other directory).
3. `/founder-brand`: lock the voice, the look of posts and the company sharing kit.
4. `/founder-launch`: turn the go-live checklist and launch day into a run sheet with owners.
