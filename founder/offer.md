# SunXFinder: The Offer

Written 2026-11-19. Method: the Offers lens in the founder pack (a summary of the value-equation and offer-stack ideas in Alex Hormozi's *$100M Offers*, in our own words).

**Inputs used:** [idea.md](idea.md), [pitch-v1.md](pitch-v1.md), the [first panel](panel/results.md), the [competitor map](competitors.md), and the product as it is built today (checked in the code).
**Missing:** `numbers.json` (no CFO run) and `board.md` (no board run). Costs below are in **founder hours**, not money. Run `/founder-skill:founder-cfo` before promising any of it at scale.

## The short answer

- **Offer name:** **SunXFinder Checked Quotes** (Arabic: **عروض مراجَعة من SunXFinder**)
- **The re-test moved the panel only a little:** 7 → **8 of 20** (35% → 40%). The one buyer who changed is a Sudanese buyer abroad, won over by the label photos before handover.
- **Sudanese abroad now say yes 5 of 5.** Local homeowners and shop owners didn't move (2 of 10 and 1 of 5).
- **What this means:** the offer is right for buyers abroad and careful researchers. For local buyers, **the problem is reach, not the offer**: they choose through family, Facebook groups and the market. Marketing has to work through those channels (company sharing, group answers, real word of mouth), not through a better app pitch.

---

## 1. The problem list

Everything between the buyer and *"a solar system that works, at a fair price, from a company that doesn't cheat me"*. Sources: P = first panel, C = competitor complaints, both in the buyer's words where possible.

| # | When | Problem | Source |
|---|---|---|---|
| 1 | Before | "I don't know what size I need, so I can't judge any offer" | P |
| 2 | Before | "Panels and batteries in the market are often fake or weak" | P, C (SSMO warning) |
| 3 | Before | "A website with strangers' ratings means less to me than what my neighbours say" | P (7 of 13) |
| 4 | Before | "I'd rather use the installer my relative used" | P |
| 5 | Before | "I don't want my number going to companies I don't know" | P |
| 6 | Before | "Is this app even working? I don't want to download something empty" | P |
| 7 | Before | "They'll just quote me shop prices plus their profit" | P (hagglers) |
| 8 | Before | "Prices change with the dollar every week" | P, C |
| 9 | Before | "Solar is too expensive for me right now" | P, C |
| 10 | Before | "I already have an installer and I'm about to decide" | P |
| 11 | Before | "I've never heard of SunXFinder. Who runs it?" | P (re-test) |
| 12 | Before | "My phone is full and data costs money" | P (re-test) |
| 13 | During | "What if nobody replies to my request?" | P |
| 14 | During | "Offers that aren't for the same size can't be compared" | P |
| 15 | During | "They'll push a bigger system than I can afford" | P (budget stretchers) |
| 16 | During | "Companies will call me non-stop" | P |
| 17 | After | "From abroad I can't check that the company actually did the work" | P |
| 18 | After | "What if they swap in cheaper parts after I pay?" | P |
| 19 | After | "If something is wrong, who makes them fix it?" | P, C |
| 20 | After | "Are the ratings real?" | P |
| 21 | After | "Will the warranty be honoured?" | P, C |

## 2. Solutions, scored

Value to the buyer (1–5) and cost to deliver (1–5, where 1 is almost free). Status: **Built** means it's already in the product (checked in the code), **Manual** means a person can do it now, **Build** means new code.

| # | Solution | Answers | Value | Cost | Status | Keep? |
|---|---|---|---|---|---|---|
| A | Free calculator: rough system size | 1, 14 | 4 | 1 | Built | ✅ Core |
| B | Written offers with brand, model, warranty and price | 2, 14, 21 | 5 | 1 | Built (offer and invoice items) | ✅ Core |
| C | **Contact details hidden until the customer accepts an offer** | 5, 16 | 5 | 1 | **Built** (`Quote::hidesCustomerContactFrom`: a company sees only the name until its offer is selected) | ✅ Guarantee |
| D | Remove any company that calls a customer it wasn't chosen by | 16 | 4 | 1 | Manual (admin) | ✅ Guarantee |
| E | **48-hour response promise:** fewer than 2 offers in 48 h → we phone companies in your city and report back | 6, 13 | 5 | 2 | Manual (~20 min per short request) | ✅ Guarantee |
| F | **Free quote check** of a quote they already have | 2, 7, 10 | 5 | 3 | Manual (~15–20 min each) | ✅ Bonus 1 |
| G | **Bring your own installer:** customer adds an installer by phone, we invite it to quote | 4, 7 | 4 | 2 | Manual (~30 min per company; also grows supply) | ✅ Bonus 2 |
| H | **Label photos before handover:** company sends photos of panel, battery and inverter labels; customer matches them to the offer before confirming | 17, 18 | 5 | 2 | Manual now (WhatsApp to SunXFinder, forwarded); **Build** later (photo upload on the handover step) | ✅ Bonus 3 |
| I | Confirm handover or "Report a problem" (job goes back to the company, complaint opened) | 19 | 5 | 1 | **Built** (handover flow, 7-day auto-confirm) | ✅ Core |
| J | Ratings only from customers who confirmed a finished job | 3, 20 | 4 | 1 | **Built** (rating returns 422 until the handover is confirmed) | ✅ Core |
| K | "Starter system, expandable later" option on the request | 9, 15 | 4 | 2 | Build (small: a checkbox and a label on the offer) | ✅ Next |
| L | Quote check by WhatsApp or website, no app needed | 10, 12 | 4 | 1 | Manual (WhatsApp Business) | ✅ Next |
| M | "Who we are" page: real names, Sudanese phone, photo | 11 | 3 | 1 | Build (content only) | ✅ Next |
| N | Offers show the date and the exchange rate used; "valid until" | 8 | 3 | 1 | Partly built (`valid_until` exists) | ✅ Next |
| O | Testing the actual equipment | 2, 18 | 5 | 5 | Not possible (no lab) | ❌ Point to SSMO's 5960 line instead |
| P | Price guarantee ("cheaper than the market") | 7 | 4 | 5 | Can't promise | ❌ |
| Q | Escrow (hold the customer's money until handover) | 18, 19 | 5 | 5 | Legal and banking work | ❌ Later, maybe with a bank partner |
| R | Instalment plans | 9 | 4 | 4 | Needs a finance partner | ❌ Later (monetization doc, "banks and finance companies") |

## 3. The stack

### Core: "Calculate, compare written offers, and pay only after you've checked the job"

- **A** calculator, **B** written offers you can compare, **I** confirm handover or report a problem, **J** ratings only from finished jobs.
- Described as the outcome: *you know what you need, you see several written prices for the same thing, and the job isn't closed until you say so.*

### Bonuses (each one answers one objection)

| Bonus | Answers | Cost |
|---|---|---|
| **1. Free quote check.** Send any quote you already have; we check sizes, brands, warranty and protection | "I already have an installer" (10), fakes (2) | ~15–20 min each. In launch week, capped at the **first 50** (≈15 hours) |
| **2. Bring your own installer.** Add the installer you trust and we invite them to quote next to the others | "I'll use my relative's installer" (4) | ~30 min per company, and each one is a new company on the platform |
| **3. Label photos before handover.** See the installed panel, battery and inverter labels before you confirm, even from abroad | "I can't check from abroad" (17), swaps (18) | ~5 min per project by hand. Build the upload into the handover step once volume grows |

### Guarantees (what happens if it doesn't deliver)

| Guarantee | Cost at a realistic claim rate |
|---|---|
| **No-spam promise.** Companies see only your name until you accept their offer. If a company you didn't choose calls you, tell us and we remove it | Already built. A removal is a few minutes in admin. Expect very few cases, since companies don't have the number |
| **48-hour promise.** If fewer than 2 companies reply within 48 hours, we phone companies in your city for you and tell you what we found | If 30% of requests fall short, at 20 requests a week: 6 × 20 min ≈ **2 hours a week**. If more than half fall short, the city doesn't have enough companies yet: stop ads there |

No money-back guarantee, because customers pay SunXFinder nothing. The guarantee is about the service: replies, privacy and proof.

### Urgency (only what's true)

- **Free quote checks in launch week: the first 50**, because each one is checked by hand. This is a real capacity limit: one person can do about 50 in a week. **Decision for you:** confirm who does the checks and that 50 is the real number.
- **Founding company** offer for companies (see [marketing.md](marketing.md)): ends at 30 companies or on 2 Feb 2027.

### The name

**SunXFinder Checked Quotes** · **عروض مراجَعة من SunXFinder**
It names what the buyer gets (quotes that someone has checked) and works with the trust positioning.

---

## 4. Value equation

Scored for the main customer (a homeowner or a Sudanese buyer abroad), 1–10. For time and effort, **higher means better** (faster, easier).

| | Before (pitch v1) | After (Checked Quotes) | What moved it |
|---|---|---|---|
| **Dream outcome:** working solar, fair price, not cheated | 7 | 8 | Label photos and the free check make "not cheated" concrete |
| **Perceived likelihood** it will happen | 3 | 5 | 48-hour promise, ratings only from finished jobs, label photos. Still low, because nobody they know has used it yet |
| **Time** to the result | 5 | 7 | 48-hour promise; quote check for people who already have a quote |
| **Effort and sacrifice** | 5 | 6 | Bring your own installer, number hidden. Still needs an app download (fix: quote check by WhatsApp) |

The biggest gap left is **likelihood**, and only real proof fixes it: named companies, real finished jobs, people in their own Facebook group. That is a marketing and time job, not an offer change.

---

## 5. Re-test: same 20 buyers, same seed (315520)

Old pitch: [pitch-v1.md](pitch-v1.md). New pitch: [pitch.md](pitch.md). Results: [panel](panel/results.md) → [panel-v2](panel-v2/results.md).

| | v1 | v2 (Checked Quotes) |
|---|---|---|
| **Would send a request** | 7 of 20 (35%) | **8 of 20 (40%)** |
| Sudanese abroad | 4 of 5 | **5 of 5** |
| Homeowners | 2 of 10 | 2 of 10 |
| Shop owners | 1 of 5 | 1 of 5 |
| Facebook-group askers | 0 of 4 | 1 of 4 |

**Who changed:** one buyer, P014 (abroad, asks in Facebook groups). In their words: label photos before handover are *"the first feature that lets [me] check the work from abroad."*

**What happened to the objections:**

| | v1 | v2 | Reading |
|---|---|---|---|
| Fear of spam calls | Raised by 4 | Still raised, but now as "is the rule really kept?" | The promise landed; buyers want proof it works |
| "Nobody will reply / empty app" | Raised by 3 | Lower; replaced by "who runs SunXFinder?" | The 48-hour promise helps; now they want a face |
| Pass reason **trust** | 7 | **3** | Dropped… |
| Pass reason **habit** | 3 | **6** | …but the same people now say "habit". They still choose through family and the market |
| Timing (already has an installer) | 2 | 2 | The quote check interests them, but not if it needs an app |

**New doubts the offer created** (add to the next version):

1. "A photo of a label proves nothing; fakes have convincing labels" (P005, P006, P004). Answer honestly: photos show *what* was installed, not whether it's genuine. Add serial numbers in the photos, and point to the SSMO 5960 line.
2. "Who runs SunXFinder?" (P010, P011, P016). Add a "who we are" page with real names and a Sudanese phone number (**M**).
3. "Companies will quote only big systems" (P019, P002, P013). Add the starter-system option (**K**).
4. "I won't install an app just for a check" (P020, P015). Offer the quote check by WhatsApp (**L**).

**Verdict:** the offer works for buyers abroad and careful researchers, and it didn't move people who choose through family, groups and the market. For them, **reach and word of mouth matter more than the offer.** Don't keep adding to the offer hoping to win them. Win them through the companies they already trust (Bonus 2), and through real stories in their groups.

---

## 6. What to build or do, in order

| # | Item | Type | Why |
|---|---|---|---|
| 1 | Quote check by WhatsApp, with a short form on the website | Manual + small content | Reaches buyers who already have an installer; no app needed |
| 2 | Label photos before handover, by hand over WhatsApp | Manual | The one change that flipped a buyer; buyers abroad are the best segment |
| 3 | "Who we are" page (EN/AR) | Content | Answers "who runs this?" |
| 4 | "Starter system, expandable later" option on the quote request | Small build | Budget stretchers all said yes; keep them |
| 5 | Photo upload on the "Customer handover" milestone (company side) and photo view before "Confirm handover" (app) | Build | Replaces the manual WhatsApp step once there are more than ~10 projects a week |
| 6 | Show the offer's date and `valid_until` clearly | Small build | Answers "prices change with the dollar" |

## Rules this offer keeps

- Every promise above is either built (contact hiding, handover confirmation, ratings after handover) or doable by hand at launch volume. If volume outgrows the manual parts, pause the promise or build it. Don't quietly stop keeping it.
- No fake scarcity: the "first 50" cap is a real limit on hand-checking. Change the number if your capacity is different.
- Don't claim the quote check proves equipment is genuine. It checks what's written, nothing more.
- Panel quotes are simulated research, never testimonials.
- Check consumer-protection rules in Sudan for the wording of the guarantees. This is not legal advice.

## Next

1. `/founder-skill:founder-cfo`: put money on the founder hours, and check the 48-hour promise and the 50 checks are affordable.
2. `/founder-skill:founder-marketing` (re-run): put Sudanese abroad first, lead with the quote check by WhatsApp, and use label photos in the diaspora ads.
