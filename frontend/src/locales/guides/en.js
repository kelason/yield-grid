export const GUIDE_UI = {
  title: 'YieldGrid documentation',
  eyebrow: 'Your growing companion',
  intro:
    'Practical guides for planning a crop, trading a harvest, and finding your way around YieldGrid. Choose a topic, follow the steps, and return whenever you need help.',
  topics: 'Browse guide topics',
  on_page: 'On this page',
  before: 'Before you begin',
  result: 'What to expect:',
  if_stuck: 'If you get stuck',
  next: 'Related guides',
  contact: 'Need a hand? Contact the team',
  back: 'Back to documentation',
  not_found: 'Guide not found',
  not_found_desc:
    'This guide address does not match a current topic. Choose a guide from the contents or return to documentation.',
  groups: {
    start: 'Start here',
    grow: 'Plan & grow',
    trade: 'Trade & connect',
    support: 'Farm support',
  },
  audiences: {
    both: 'For farmers and buyers',
    farmer: 'For farmers',
    buyer: 'For buyers',
  },
}

export default {
  'getting-started': {
    title: 'Getting started',
    summary: 'Create the right account, verify your email, and choose your first task.',
    prerequisites: {
      prepare:
        'You need an email address you can open. Use your own account and keep your password private.',
    },
    sections: {
      'choose-role': {
        title: 'Choose your role',
        intro: 'Your role determines which workspace and actions you use.',
        steps: {
          follow: {
            instruction:
              'Choose Farmer if you plan crops or sell harvests. Choose Buyer if you purchase crops or post crop requirements.',
            result: 'Your dashboard and navigation match the role selected at registration.',
          },
        },
        notes: {},
        recovery: {},
      },
      register: {
        title: 'Create an account',
        intro: 'Registration starts from Get Started on the public site.',
        steps: {
          follow: {
            instruction:
              'Open registration. Enter your name, email, password and matching password confirmation, then choose a role. Review the details and follow the confirmation before creating the account.',
            result:
              'You are signed in to your role dashboard. Address details are optional during registration; you can add them later.',
          },
        },
        notes: {},
        recovery: {},
      },
      'verify-email': {
        title: 'Verify your email',
        intro:
          'Email verification unlocks protected actions. It is separate from administrative farm review.',
        steps: {
          follow: {
            instruction:
              'Open the verification message sent to your email and follow its link. If it has expired or is missing, use the verification banner to resend it after the displayed cooldown. Check spam as well as your inbox.',
            result:
              'A valid link verifies your email. You do not need to wait for a farm verification badge to create a farm, request analysis or post a manual harvest.',
          },
        },
        notes: {},
        recovery: {},
      },
      'sign-in-and-recover': {
        title: 'Sign in or recover access',
        intro: 'Use the same email you registered with.',
        steps: {
          follow: {
            instruction:
              'Open Log in and enter your email and password. If you forgot the password, choose password recovery, request a reset email and follow the link to set a new password.',
            result:
              'Successful sign-in returns you to your workspace. Recovery requires access to your registered email.',
          },
        },
        notes: {},
        recovery: {},
      },
      'next-steps': {
        title: 'Choose a first task',
        intro: 'There is more than one way to start using YieldGrid.',
        steps: {
          follow: {
            instruction:
              'Farmers can create a farm for crop planning or post an existing harvest directly. Buyers can browse the marketplace or post a crop demand. Read the relevant guide before confirming an action.',
            result:
              'Planning, direct selling and buyer demands are separate paths. You can learn other tools when you need them.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'profile-addresses-language': {
    title: 'Profile, addresses & language',
    summary:
      'Find your own profile, save marketplace addresses and read YieldGrid in your preferred language.',
    prerequisites: {
      prepare: 'Sign in to manage your profile and saved addresses.',
    },
    sections: {
      'open-profile': {
        title: 'Open your own profile',
        intro: 'Your profile is available for both Farmer and Buyer accounts.',
        steps: {
          follow: {
            instruction:
              'In the dashboard navigation, open your profile. On a phone, open the navigation drawer and select your own name/profile entry.',
            result:
              'You can review your account details and access address management from your own profile.',
          },
        },
        notes: {},
        recovery: {},
      },
      'saved-addresses': {
        title: 'Save an address',
        intro: 'Some marketplace actions need a saved address.',
        steps: {
          follow: {
            instruction:
              'Open address management in your own profile. Add the requested contact and location details, review the address and confirm saving. Check the selected address before posting a demand or submitting an offer.',
            result:
              'The saved address becomes available to the supported trading forms. Creating an account does not require an address, but posting demands and submitting offers do.',
          },
        },
        notes: {},
        recovery: {},
      },
      'change-language': {
        title: 'Change the language',
        intro: 'English, Tagalog and Cebuano are available across the app.',
        steps: {
          follow: {
            instruction:
              'Use the language control in the public header or dashboard. On a phone, open the menu or navigation drawer to find it. Choose your preferred language.',
            result:
              'Text changes without changing the task you are viewing. The selection is remembered when storage is available; a signed-in preference can also be saved to your account.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'troubleshooting-and-support': {
    title: 'Troubleshooting & support',
    summary:
      'Find the next useful step when an account, map, analysis or trade does not work as expected.',
    prerequisites: {
      prepare:
        'Keep the error message and the details of the task you were trying. Never share your password or verification link.',
    },
    sections: {
      'account-access': {
        title: 'Account or verification trouble',
        intro: 'Start by checking which account and email you are using.',
        steps: {
          follow: {
            instruction:
              'Confirm your email address, inspect spam and resend the verification message from the banner when the cooldown allows it. Use password recovery if sign-in fails because you forgot the password.',
            result:
              'A new verification link or password reset can restore the next step. Email verification is different from a farm review badge.',
          },
        },
        notes: {},
        recovery: {},
      },
      'farm-and-analysis': {
        title: 'Map or crop analysis trouble',
        intro: 'A rejected drawing or failed analysis needs review, not repeated blind submission.',
        steps: {
          follow: {
            instruction:
              'Read the displayed reason. Adjust a drawing that overlaps a restricted area or lies outside the resolved city boundary. For failed analysis, use the available retry after checking your plot and inputs.',
            result:
              'The form or retry offers another attempt. Analysis runs asynchronously; waiting for a result does not publish a contract.',
          },
        },
        notes: {},
        recovery: {},
      },
      'trading-and-payments': {
        title: 'Trading or payment trouble',
        intro: 'A pending state does not mean a payment was received.',
        steps: {
          follow: {
            instruction:
              'Check the current purchase or offer status and the displayed error before retrying. Add a saved address if the form requires it. Keep your draft, and confirm cash receipt only after receiving the money.',
            result:
              'You can distinguish a missing prerequisite from a payment still being processed. An accepted offer is allocated, not automatically paid.',
          },
        },
        notes: {},
        recovery: {},
      },
      'messages-and-downloads': {
        title: 'Messages or downloads unavailable',
        intro: 'Chat is connected to eligible trades, and reports may need time to generate.',
        steps: {
          follow: {
            instruction:
              'Check that you share a qualifying purchase or an accepted demand offer before starting chat. If messages or a generated document fail to load, use the shown retry or download action when it becomes available.',
            result:
              'Eligible conversations and ready documents can be opened. The app does not provide unrestricted chat with every profile.',
          },
        },
        notes: {},
        recovery: {},
      },
      'contact-support': {
        title: 'Ask for help',
        intro: 'Give the team enough context to understand the problem.',
        steps: {
          follow: {
            instruction:
              'Open Contact on the public site, or Report an issue in the member dashboard. Describe what you tried, the page involved and the error. Review the message before confirming submission.',
            result:
              'The team receives your inquiry or issue record. Keep passwords, payment credentials and private verification links out of the message.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'farms-and-plots': {
    title: 'Farms & plots',
    summary: 'Organize your growing areas and save a plot boundary with its soil information.',
    prerequisites: {
      prepare: 'Use a Farmer account with a verified email to save farming records.',
    },
    sections: {
      'create-farm': {
        title: 'Create a farm',
        intro: 'A farm groups your plots under one name.',
        steps: {
          follow: {
            instruction:
              'Open My Farms, choose the create-farm action and enter a farm name. Add location details and total area if known, review the form and confirm creation.',
            result:
              'The farm appears in My Farms. Name is required; address, city and other location fields are optional.',
          },
        },
        notes: {},
        recovery: {},
      },
      'draw-a-plot': {
        title: 'Draw a plot boundary',
        intro: 'The plot planner uses a map to record a growing area.',
        steps: {
          follow: {
            instruction:
              'Open Manage plots for your farm. Locate the correct area using the search and zoom controls. Select the polygon drawing tool, place the boundary points and finish the shape.',
            result:
              'An unsaved boundary appears and the plot form opens. Check its location and shape before saving.',
          },
        },
        notes: {},
        recovery: {},
      },
      'save-a-plot': {
        title: 'Review and save a plot',
        intro: 'Area is calculated from the boundary you draw.',
        steps: {
          follow: {
            instruction:
              'Enter a plot name and choose the soil type that best matches the area. Review the calculated area, then save and confirm. If the drawing is wrong, cancel the unsaved form and draw again.',
            result:
              'The saved plot belongs to this farm and is available for analysis. The current planner does not offer editing or deleting a saved boundary.',
          },
        },
        notes: {},
        recovery: {},
      },
      'map-problems': {
        title: 'Resolve map or boundary problems',
        intro:
          'The app can reject drawings in mapped restricted areas or outside the resolved city boundary.',
        steps: {
          follow: {
            instruction:
              'Read the displayed reason, check the farm location and move or redraw the unsaved polygon where appropriate. If you cannot resolve the location, contact support with the farm name and error message.',
            result:
              'A corrected drawing can be reviewed again. A map or calculated area is an app planning aid, not a legal land survey.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'crop-advisor': {
    title: 'AI Crop Advisor',
    summary:
      'Request plot-informed crop suggestions, compare options and choose whether to publish a future harvest.',
    prerequisites: {
      prepare:
        'Create a plot first. Record the soil and location carefully; recommendations depend on these inputs.',
    },
    sections: {
      'request-analysis': {
        title: 'Request crop analysis',
        intro: 'Analysis is tied to a saved plot.',
        steps: {
          follow: {
            instruction:
              'Open Recommendations, select a plot and review its soil and location. Set the available produce types, subtypes, irrigation level and goal as needed. Choose the analysis action and confirm the request.',
            result:
              'A processing state appears. The analysis runs in the background; you can wait for the updated results rather than repeatedly submitting it.',
          },
        },
        notes: {},
        recovery: {},
      },
      'read-results': {
        title: 'Read results and projections',
        intro:
          'Recommendations explain crop suitability using the recorded inputs and available information.',
        steps: {
          follow: {
            instruction:
              'Open a crop card and read its reasoning, confidence and projected yield or financial information. Consider the displayed weather context and your own field conditions. Use the shown retry if analysis fails.',
            result:
              'You have options to compare. Projections and AI confidence do not guarantee yields, profit or weather outcomes; consult local agricultural guidance for field decisions.',
          },
        },
        notes: {},
        recovery: {},
      },
      'publish-a-contract': {
        title: 'Publish a future harvest contract',
        intro: 'Analysis and publication are separate decisions.',
        steps: {
          follow: {
            instruction:
              'Choose Accept & Contract on an eligible recommendation. Review the quantity, price per kg, harvest date and other terms in the publishing form, then confirm publication.',
            result:
              'The contract appears in My Contracts. A recommendation already published or rejected cannot be published again; requesting analysis alone never lists your produce.',
          },
        },
        notes: {},
        recovery: {},
      },
      'compare-crops': {
        title: 'Compare crops',
        intro: 'Compatibility is a separate comparison tool.',
        steps: {
          follow: {
            instruction:
              'Open Compatibility, select the two crops and use the available comparison action. Read the explanation before choosing rotation or intercropping arrangements.',
            result:
              'You can review compatibility without publishing a listing or completing an analysis. The result supports your judgment rather than replacing field advice.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'selling-harvests': {
    title: 'Selling an existing harvest',
    summary:
      'Post a manual harvest listing directly, with or without a linked farm or AI recommendation.',
    prerequisites: {
      prepare:
        'Verify your Farmer account email. Prepare crop, quantity, price and availability details you can stand behind.',
    },
    sections: {
      'manual-listing': {
        title: 'Enter the harvest details',
        intro: 'Post Manual Harvest is a direct selling path.',
        steps: {
          follow: {
            instruction:
              'Open Post Harvest from My Contracts. Choose the crop, shelf life and listing title; enter description, quantity in kg and price per kg. Mark the harvest available now or set its estimated harvest date.',
            result:
              'The draft describes what a buyer can purchase. Observe the form limits and enter quantities and dates you can reasonably supply.',
          },
        },
        notes: {},
        recovery: {},
      },
      'optional-source': {
        title: 'Link a source only if useful',
        intro: 'Harvest Source is optional.',
        steps: {
          follow: {
            instruction:
              'Leave the source empty if you do not have a farm record, or select one of your own farms and a plot belonging to it. Changing the farm clears the previous plot choice.',
            result:
              'You can post a manual harvest without a farm, drawn plot or AI recommendation. Source linking supplies context; it does not create a verification badge.',
          },
        },
        notes: {},
        recovery: {},
      },
      'review-and-publish': {
        title: 'Review and publish',
        intro: 'Price context can help you assess a draft.',
        steps: {
          follow: {
            instruction:
              'Use the embedded Price Guide if needed and check its source/date. Review the crop, quantity, unit price, availability and source. Submit the form and confirm the listing only when the details are correct.',
            result:
              'A successful listing takes you to My Contracts. If the request fails, read the form error and correct or retry the draft; reference prices do not guarantee a sale.',
          },
        },
        notes: {},
        recovery: {},
      },
      'manage-listings': {
        title: 'Follow sales and receipt',
        intro: 'My Contracts keeps your listing and contract records together.',
        steps: {
          follow: {
            instruction:
              'Open My Contracts and review the available controls and current state on your listing. For qualifying trades, open messaging to coordinate. For cash purchases, open Cash Approvals and record receipt only after the payment reaches you.',
            result:
              'A pending cash request remains distinct from money received. Follow the displayed cancellation or other confirmation controls where available.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'verification-and-trust-score': {
    title: 'Verification & farmer trust score',
    summary:
      'Understand provenance badges and the activity-based score shown in your farmer workspace.',
    prerequisites: {
      prepare:
        'Farm badges and email verification describe different checks. Neither a badge nor a score guarantees a trade or loan.',
    },
    sections: {
      'email-and-farm-verification': {
        title: 'Read verification badges correctly',
        intro: 'Email and farm verification describe different things.',
        steps: {
          follow: {
            instruction:
              'Verify your email through its email link for protected actions. Read farm and plot review statuses as administrative provenance checks; a plot badge requires both its farm and plot to be verified.',
            result:
              'Pending farm review does not block the independent creation, analysis and manual listing paths. Do not treat a badge as an assurance of yield, delivery or payment.',
          },
        },
        notes: {},
        recovery: {},
      },
      'read-your-score': {
        title: 'Understand the farmer trust score',
        intro: 'The score summarizes activity recorded within YieldGrid.',
        steps: {
          follow: {
            instruction:
              'Open Farmer Trust Score and inspect the breakdown, history and improvement tips. Consider plot activity, recommendations, contracts, offers, transactions and platform tenure together.',
            result:
              'You can see what contributes to the internal score. It is not a bank credit decision, loan approval or guaranteed measure of real-world farming ability.',
          },
        },
        notes: {},
        recovery: {},
      },
      'download-report': {
        title: 'Generate a shareable report',
        intro: 'A report is generated from the score information.',
        steps: {
          follow: {
            instruction:
              'Choose the report-generation action, review and confirm it, then wait for the ready state and download the PDF. If a limit or expiry is shown, follow that message before generating another report.',
            result:
              'You can share the report where useful. YieldGrid does not establish that a lender accepts it or will grant a loan.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'insurance-and-rsbsa': {
    title: 'Crop insurance & RSBSA',
    summary: 'Prepare enrollment documents and keep policy, claim and reminder records organized.',
    prerequisites: {
      prepare:
        'Use a Farmer account and a plot for the current enrollment form. Confirm eligibility and deadlines with your local agriculture office or PCIC.',
    },
    sections: {
      'rsbsa-records': {
        title: 'Keep RSBSA details',
        intro: 'The app stores the registration details you provide.',
        steps: {
          follow: {
            instruction:
              'Open Crop Insurance and enter your RSBSA information/status accurately. Review and confirm saving; contact your agriculture office if you need to establish or correct the official registration.',
            result:
              'Your recorded information can help prepare documents. Saving a number in YieldGrid does not verify or create official RSBSA registration.',
          },
        },
        notes: {},
        recovery: {},
      },
      'prepare-enrollment': {
        title: 'Prepare an enrollment record',
        intro: 'The current guide supports rice/corn programs and wet/dry seasons.',
        steps: {
          follow: {
            instruction:
              'Select a plot, program, season and year in the enrollment guide. Review the details and confirm creating the record. Check the document requirements with your local office before treating the record as an application.',
            result:
              'An enrollment preparation record appears. It does not purchase coverage or automatically establish eligibility.',
          },
        },
        notes: {},
        recovery: {},
      },
      'download-and-submit': {
        title: 'Print and submit outside the app',
        intro: 'Official submission happens outside YieldGrid.',
        steps: {
          follow: {
            instruction:
              'Generate the enrollment pack through the confirmation, wait until ready and download it. Print/review the forms and required attachments, then submit to the Municipal Agriculture Office as instructed by the local office. Record the submitted status only after doing so.',
            result:
              'The pack helps you prepare. YieldGrid does not electronically enroll you with PCIC or confirm acceptance of the external application.',
          },
        },
        notes: {},
        recovery: {},
      },
      'policy-and-claims': {
        title: 'Track policy and claim records',
        intro: 'The records reflect information you enter after external steps.',
        steps: {
          follow: {
            instruction:
              'When issued externally, record the CIC/policy details accurately. Add and update claim records only to match actual submissions and decisions; review reminders and confirm changes before saving.',
            result:
              'You have an organized record of your documents and dates. The insurer/local office decides coverage, claim approval and payment; in-app status changes do not make those decisions.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'buying-harvests': {
    title: 'Buying a harvest',
    summary:
      'Find available harvests or future contracts, review quantities and make an informed purchase.',
    prerequisites: {
      prepare: 'Guests can browse. Use a Buyer account with verified email for checkout.',
    },
    sections: {
      'browse-and-filter': {
        title: 'Browse and filter crops',
        intro: 'Marketplace is public for discovery.',
        steps: {
          follow: {
            instruction:
              'Open Browse Market and enter a crop or title search. Adjust minimum/maximum price where useful; filters update as you type. Browse more pages to inspect the available results.',
            result:
              'You see matching listings and contracts. An empty result can reflect your filters; clear or broaden them before assuming there is no produce.',
          },
        },
        notes: {},
        recovery: {},
      },
      'inspect-listing': {
        title: 'Inspect the listing',
        intro: 'Available harvest and future harvest have different timing.',
        steps: {
          follow: {
            instruction:
              'Open View Details. Read crop, quantity, price per kg, harvest availability/date, farmer and any source badge. Use displayed price context carefully and consider whether the timing matches your needs.',
            result:
              'You can distinguish what is available now from a future contract. A provenance badge is not a guarantee of delivery or crop quality.',
          },
        },
        notes: {},
        recovery: {},
      },
      'review-checkout': {
        title: 'Review your checkout',
        intro: 'The checkout recalculates the amount for your chosen quantity.',
        steps: {
          follow: {
            instruction:
              'Sign in as a verified Buyer. Enter the purchase quantity within the shown limits, choose an available payment option and review the total and any downpayment. Proceed and confirm only when the terms are correct.',
            result:
              'Online checkout can open PayMongo; cash creates a request for farmer confirmation. Do not assume a confirmation click means cash was received.',
          },
        },
        notes: {},
        recovery: {},
      },
      'track-purchase': {
        title: 'Follow the purchase',
        intro: 'My Purchases shows the app-recorded purchase state.',
        steps: {
          follow: {
            instruction:
              'Open My Purchases after checkout and review the payment status and amount paid. Use eligible transaction messaging to coordinate details with the farmer. Use only the cancellation actions the current record provides.',
            result:
              'Payment progress is visible in the app. Physical delivery arrangements are coordinated with the trading partner; the app does not promise a delivery service.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'payments-and-purchases': {
    title: 'Payments & purchases',
    summary:
      'Understand checkout amounts, online payments, cash confirmations and purchase status.',
    prerequisites: {
      prepare:
        'Review the listing and the amount shown in checkout. Payment and delivery are different events.',
    },
    sections: {
      'payment-options': {
        title: 'Choose a payment option',
        intro: 'Current checkout offers online PayMongo and cash where supported.',
        steps: {
          follow: {
            instruction:
              'Review the options in the active checkout. For online payment, confirm before following the hosted checkout; for Cash (Off-Site), follow the displayed cash instructions and coordinate actual payment with the farmer.',
            result:
              'The option determines the next steps. Never enter payment credentials into community posts, chat or support messages.',
          },
        },
        notes: {},
        recovery: {},
      },
      'deposits-and-totals': {
        title: 'Read deposits and totals',
        intro: 'The amount depends on quantity and harvest availability.',
        steps: {
          follow: {
            instruction:
              'Check the chosen quantity multiplied by price per kg and the amount due now. A future harvest currently shows a 10% downpayment; an available harvest uses full payment. Review the remaining amount separately.',
            result:
              'A downpayment is not full settlement. The amount displayed in checkout is the reference for your specific order; recheck it whenever you change quantity.',
          },
        },
        notes: {},
        recovery: {},
      },
      'cash-confirmation': {
        title: 'Confirm actual cash receipt',
        intro: 'Choosing cash creates a pending record, not money received.',
        steps: {
          follow: {
            instruction:
              'Buyers arrange the cash payment with the farmer. Farmers open Cash Approvals, inspect the purchase and record only the amount actually received, choosing partial or full receipt as appropriate and confirming the action.',
            result:
              'The record updates after receipt is confirmed. A partial receipt leaves an outstanding amount; do not mark full payment before receiving it.',
          },
        },
        notes: {},
        recovery: {},
      },
      'statuses-and-cancellation': {
        title: 'Check status before retrying or cancelling',
        intro: 'A return page alone is not proof that payment completed.',
        steps: {
          follow: {
            instruction:
              'Read My Purchases or the offer payment record. If pending, check progress before starting another request. If failed, read the error and use the supported retry. If cancellation is offered, review its consequences and confirm it.',
            result:
              'The current record distinguishes pending, failed and completed payment. Completed payment does not itself certify delivery, and cancellation is not a promise of an automatic refund.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'demands-and-offers': {
    title: 'Buyer demands & farmer offers',
    summary:
      'Request a crop or respond to a buyer, then follow allocation, payment, delivery and completion.',
    prerequisites: {
      prepare:
        'Verify your email and save a marketplace address before posting a demand or submitting an offer.',
    },
    sections: {
      'post-demand': {
        title: 'Buyer: post a crop demand',
        intro: 'A demand tells farmers what you need and when.',
        steps: {
          follow: {
            instruction:
              'Save an address in your own profile, then open Post Demand. Enter title, crop, quantity, target price, delivery address, needed-by date, expiry and description. Review the dates and confirm posting.',
            result:
              'The demand appears in My Demands and eligible farmers can respond. A target price is a request, not a fixed promise of supply.',
          },
        },
        notes: {},
        recovery: {},
      },
      'submit-offer': {
        title: 'Farmer: respond with an offer',
        intro: 'An offer is tied to a specific open buyer demand.',
        steps: {
          follow: {
            instruction:
              'Save your marketplace address and open Crop Demands. Inspect the crop, dates and remaining quantity. Enter an offer quantity within that remaining need, price per kg and optional message, then review and confirm.',
            result:
              'Track the offer in My Offers. Only one active offer from you per demand is allowed; use the displayed withdrawal action where available before replacing it.',
          },
        },
        notes: {},
        recovery: {},
      },
      'decide-offers': {
        title: 'Buyer: review and decide offers',
        intro: 'A demand can be supplied by several farmers.',
        steps: {
          follow: {
            instruction:
              'Open My Demands, inspect the farmer offers and compare quantity, price and terms. Review the accept/reject confirmation for each decision. Use the remaining demand quantity to understand what is still needed.',
            result:
              'Accepting allocates that offer quantity and can unlock chat, but does not pay it. Fully allocated is different from fulfilled.',
          },
        },
        notes: {},
        recovery: {},
      },
      'pay-and-deliver': {
        title: 'Pay the accepted offer and record delivery',
        intro: 'Acceptance, payment and delivery are separate stages.',
        steps: {
          follow: {
            instruction:
              'The buyer opens the accepted offer checkout, reviews online/cash terms and confirms payment. For cash, the farmer records actual receipt. When the offer is in a paid or partially paid state and delivery occurs, the farmer reviews and confirms marking it delivered.',
            result:
              'The offer reflects the recorded progress. Coordinate delivery with the trading partner; do not mark delivered merely because a payment was made.',
          },
        },
        notes: {},
        recovery: {},
      },
      'complete-and-settle': {
        title: 'Confirm completion and record the balance',
        intro: 'The buyer completes an offer after farmer-recorded delivery.',
        steps: {
          follow: {
            instruction:
              'After receiving the delivered crop, the buyer reviews and confirms completion. For a delivered/completed offer with an outstanding online-downpayment balance, the farmer uses the supported balance-settlement confirmation only after full payment is actually received.',
            result:
              'Completion and amount paid stay distinct. Balance settlement records full receipt; it does not initiate another automatic PayMongo checkout.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'price-guide': {
    title: 'Using the Price Guide',
    summary:
      'Read price references with their source, date and limitations before choosing your trading terms.',
    prerequisites: {
      prepare:
        'Price Guide appears within supported listing, contract, demand and offer forms; it is not a separate dashboard page.',
    },
    sections: {
      'open-price-guide': {
        title: 'Open the embedded guide',
        intro: 'Price guidance is available alongside supported price fields.',
        steps: {
          follow: {
            instruction:
              'Open the Price Guide control in a listing, contract-publishing, demand or offer form. Check the selected crop and location context and read the available price reference.',
            result:
              'You have context for choosing your own price. It is not a required selling price, and unavailable guidance does not mean your crop has no value.',
          },
        },
        notes: {},
        recovery: {},
      },
      'sources-and-freshness': {
        title: 'Read the source and date',
        intro: 'Different price sources describe different evidence.',
        steps: {
          follow: {
            instruction:
              'Read the source, as-of date and any stale indicator. The current guide checks YieldGrid trade data, then DA reference information, then an AI estimate when earlier sources are unavailable. Compare the context rather than treating these as identical local prices.',
            result:
              'An AI estimate remains labeled AI; it is not a DA quotation. No source guarantees the newest farm-gate price or what a buyer will pay.',
          },
        },
        notes: {},
        recovery: {},
      },
      'unavailable-prices': {
        title: 'When no useful price is available',
        intro: 'Reference coverage and data freshness vary.',
        steps: {
          follow: {
            instruction:
              'Read the unavailable/error state and try the displayed retry if offered. Check the crop/location selection and use other reliable local information when deciding a price. Review the price you enter before confirming a trade.',
            result:
              'You retain control of your terms. The guide does not replace negotiation, local market checks or the need to understand your costs.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'community-and-messaging': {
    title: 'Community & messaging',
    summary:
      'Discuss practical questions in the community and coordinate qualifying trades through messages.',
    prerequisites: {
      prepare:
        'Verify your email for member community and messaging actions. Chat requires an eligible transaction with the other member.',
    },
    sections: {
      'chat-eligibility': {
        title: 'Know when chat is available',
        intro: 'Transaction chat connects the people in a qualifying trade.',
        steps: {
          follow: {
            instruction:
              'Open messaging from a qualifying purchase or buyer-accepted demand offer. Both members need the relevant account access; a failed payment or an unaccepted offer does not independently unlock a conversation.',
            result:
              'An accepted offer can enable chat before payment. Knowing another profile alone does not permit unrestricted messaging.',
          },
        },
        notes: {},
        recovery: {},
      },
      'use-messages': {
        title: 'Coordinate through messages',
        intro: 'Keep trade discussions specific and respectful.',
        steps: {
          follow: {
            instruction:
              'Open the conversation, read the history and compose a message within the displayed limit. Review and confirm sending; attach only supported files within the shown size limits. Use retry if message history fails to load.',
            result:
              'Messages and supported attachments appear in the conversation. Do not share account passwords, verification links or payment credentials.',
          },
        },
        notes: {},
        recovery: {},
      },
      'forum-participation': {
        title: 'Join a community discussion',
        intro: 'The forum is for shared practical knowledge.',
        steps: {
          follow: {
            instruction:
              'Browse categories and threads, search a topic and read existing replies. When contributing, use the available thread/reply form, respect text/file limits and review its confirmation. Use voting or accepted-answer controls only where available to you.',
            result:
              'Your contributions join the discussion. Community experience and AI information should be assessed alongside local expertise, not treated as guaranteed advice.',
          },
        },
        notes: {},
        recovery: {},
      },
      'report-content': {
        title: 'Report a concern',
        intro: 'Use the reporting controls rather than escalating a public dispute.',
        steps: {
          follow: {
            instruction:
              'Open the report action on supported content, choose a reason and describe the concern within the limit. Review and confirm submission. For technical problems with the app itself, use Report an issue or Contact.',
            result:
              'The team receives a report for review. A submitted report is not an immediate decision about the content or the other account.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
}
