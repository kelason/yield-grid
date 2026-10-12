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
              'Open Contact on the public site, or Report Issue in the member dashboard. Describe what you tried, the page involved and the error. Review the message before confirming submission.',
            result:
              'The team receives your inquiry or issue record. Keep passwords, payment credentials and private verification links out of the message.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
}
