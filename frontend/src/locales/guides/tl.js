export const GUIDE_UI = {
  title: 'Dokumentasyon ng YieldGrid',
  eyebrow: 'Kasama sa pagsasaka',
  intro:
    'Mga praktikal na gabay sa pagpaplano ng pananim, pagbebenta ng ani, at paggamit ng YieldGrid. Pumili ng paksa, sundin ang mga hakbang, at bumalik kapag kailangan mo ng tulong.',
  topics: 'Mga paksa ng gabay',
  on_page: 'Sa pahinang ito',
  before: 'Bago magsimula',
  result: 'Inaasahang resulta:',
  if_stuck: 'Kung may problema',
  next: 'Mga kaugnay na gabay',
  contact: 'Kailangan ng tulong? Makipag-ugnayan sa team',
  back: 'Bumalik sa dokumentasyon',
  not_found: 'Hindi nakita ang gabay',
  not_found_desc:
    'Walang kasalukuyang paksa sa address na ito. Pumili sa mga paksa o bumalik sa dokumentasyon.',
  groups: {
    start: 'Magsimula rito',
    grow: 'Magplano at magtanim',
    trade: 'Kalakalan at ugnayan',
    support: 'Suporta sa bukid',
  },
  audiences: {
    both: 'Para sa magsasaka at mamimili',
    farmer: 'Para sa magsasaka',
    buyer: 'Para sa mamimili',
  },
}

export default {
  'getting-started': {
    title: 'Pagsisimula',
    summary: 'Gumawa ng tamang account, beripikahin ang email, at piliin ang unang gagawin.',
    prerequisites: {
      prepare:
        'Kailangan mo ng email na nabubuksan mo. Gamitin ang sariling account at panatilihing pribado ang password.',
    },
    sections: {
      'choose-role': {
        title: 'Piliin ang tungkulin',
        intro: 'Ang tungkulin ang batayan ng workspace at mga gawaing magagamit.',
        steps: {
          follow: {
            instruction:
              'Piliin ang Magsasaka para magplano o magbenta ng ani. Piliin ang Mamimili para bumili o mag-post ng kailangan mong pananim.',
            result:
              'Ang dashboard at nabigasyon ay naaayon sa napiling tungkulin sa pagpaparehistro.',
          },
        },
        notes: {},
        recovery: {},
      },
      register: {
        title: 'Gumawa ng account',
        intro: 'Magsimula sa Get Started ng pampublikong site.',
        steps: {
          follow: {
            instruction:
              'Buksan ang pagpaparehistro. Ilagay ang pangalan, email, password at kaparehong kumpirmasyon, at pumili ng tungkulin. Suriin ang detalye at sundin ang kumpirmasyon bago gumawa ng account.',
            result:
              'Naka-sign in ka sa dashboard ng iyong tungkulin. Opsyonal ang address sa pagpaparehistro at maaari itong idagdag sa ibang oras.',
          },
        },
        notes: {},
        recovery: {},
      },
      'verify-email': {
        title: 'Beripikahin ang email',
        intro:
          'Binubuksan ng email verification ang mga protektadong gawain. Hiwalay ito sa pagsusuri ng admin sa bukid.',
        steps: {
          follow: {
            instruction:
              'Buksan ang mensahe ng beripikasyon at sundin ang link. Kung paso o nawawala ito, gamitin ang banner upang muling magpadala matapos ang ipinakitang paghihintay. Tingnan din ang spam.',
            result:
              'Nabeberipika ng wastong link ang email. Hindi kailangang maghintay ng farm verification badge upang gumawa ng bukid, magsuri ng pananim o mag-post ng manual na ani.',
          },
        },
        notes: {},
        recovery: {},
      },
      'sign-in-and-recover': {
        title: 'Mag-sign in o ibalik ang access',
        intro: 'Gamitin ang email na ipinarehistro.',
        steps: {
          follow: {
            instruction:
              'Buksan ang Log in at ilagay ang email at password. Kung nakalimutan ang password, piliin ang pagbawi nito, humiling ng reset email at sundin ang link para sa bagong password.',
            result:
              'Ibabalik ka ng matagumpay na pag-sign in sa workspace. Kailangan ang access sa rehistradong email upang mabawi ang account.',
          },
        },
        notes: {},
        recovery: {},
      },
      'next-steps': {
        title: 'Piliin ang unang gagawin',
        intro: 'May higit sa isang paraan ng pagsisimula sa YieldGrid.',
        steps: {
          follow: {
            instruction:
              'Maaaring gumawa ng bukid para sa pagpaplano o direktang mag-post ng ani ang magsasaka. Maaaring tumingin sa marketplace o mag-post ng demand ang mamimili. Basahin ang gabay bago kumpirmahin ang gawain.',
            result:
              'Magkahiwalay na landas ang pagpaplano, direktang pagbebenta at buyer demand. Matututuhan ang iba pang tool kapag kailangan na.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'profile-addresses-language': {
    title: 'Profile, address at wika',
    summary:
      'Hanapin ang sariling profile, mag-save ng marketplace address at gamitin ang YieldGrid sa napiling wika.',
    prerequisites: {
      prepare: 'Mag-sign in para pamahalaan ang profile at mga naka-save na address.',
    },
    sections: {
      'open-profile': {
        title: 'Buksan ang sariling profile',
        intro: 'May profile ang parehong Magsasaka at Mamimili.',
        steps: {
          follow: {
            instruction:
              'Buksan ang profile sa nabigasyon ng dashboard. Sa telepono, buksan ang navigation drawer at piliin ang sariling pangalan o profile.',
            result:
              'Masusuri mo ang account at mapapamahalaan ang mga address mula sa sariling profile.',
          },
        },
        notes: {},
        recovery: {},
      },
      'saved-addresses': {
        title: 'Mag-save ng address',
        intro: 'Kailangan ng naka-save na address ang ilang gawain sa marketplace.',
        steps: {
          follow: {
            instruction:
              'Buksan ang pamamahala ng address sa sariling profile. Ilagay ang hinihinging contact at lokasyon, suriin at kumpirmahin ang pag-save. Tingnan ang napiling address bago mag-post ng demand o magsumite ng offer.',
            result:
              'Magagamit ang naka-save na address sa mga kaukulang form. Hindi ito kailangan sa paggawa ng account, ngunit kailangan sa pag-post ng demand at pagsusumite ng offer.',
          },
        },
        notes: {},
        recovery: {},
      },
      'change-language': {
        title: 'Palitan ang wika',
        intro: 'May English, Tagalog at Cebuano sa app.',
        steps: {
          follow: {
            instruction:
              'Gamitin ang language control sa header o dashboard. Sa telepono, buksan ang menu o navigation drawer para makita ito. Piliin ang gustong wika.',
            result:
              'Nagbabago ang teksto nang hindi pinapalitan ang tinitingnang gawain. Natatandaan ang pinili kapag gumagana ang storage; maaari rin itong ma-save sa naka-sign in na account.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'troubleshooting-and-support': {
    title: 'Mga problema at suporta',
    summary:
      'Hanapin ang susunod na hakbang kapag hindi gumagana nang inaasahan ang account, mapa, pagsusuri o kalakalan.',
    prerequisites: {
      prepare:
        'Itabi ang mensahe ng error at detalye ng gagawin. Huwag ibahagi ang password o verification link.',
    },
    sections: {
      'account-access': {
        title: 'Problema sa account o beripikasyon',
        intro: 'Tingnan muna kung aling account at email ang ginagamit.',
        steps: {
          follow: {
            instruction:
              'Suriin ang email, tingnan ang spam at muling ipadala ang verification message mula sa banner pagkatapos ng paghihintay. Gamitin ang password recovery kung nakalimutan ang password.',
            result:
              'Maaaring maibalik ng bagong verification link o password reset ang access. Hiwalay ang email verification sa farm review badge.',
          },
        },
        notes: {},
        recovery: {},
      },
      'farm-and-analysis': {
        title: 'Problema sa mapa o pagsusuri',
        intro: 'Suriin ang tinanggihang drawing o nabigong pagsusuri bago muling magsumite.',
        steps: {
          follow: {
            instruction:
              'Basahin ang dahilan. Ayusin ang drawing na sumasakop sa restricted area o nasa labas ng natukoy na hangganan ng lungsod. Sa nabigong pagsusuri, gamitin ang retry matapos suriin ang plot at mga input.',
            result:
              'May pagkakataong subukan muli sa form o retry. Hindi agad natatapos ang pagsusuri at hindi ito awtomatikong naglalathala ng kontrata.',
          },
        },
        notes: {},
        recovery: {},
      },
      'trading-and-payments': {
        title: 'Problema sa kalakalan o bayad',
        intro: 'Hindi ibig sabihin ng pending na natanggap na ang bayad.',
        steps: {
          follow: {
            instruction:
              'Tingnan ang status ng purchase o offer at error bago subukang muli. Magdagdag ng address kung kailangan ng form. Itabi ang draft at kumpirmahin lang ang cash receipt kapag natanggap na ang pera.',
            result:
              'Malalaman kung may kulang na kailangan o pinoproseso pa ang bayad. Nakalaan ang tinanggap na offer, ngunit hindi pa awtomatikong bayad.',
          },
        },
        notes: {},
        recovery: {},
      },
      'messages-and-downloads': {
        title: 'Hindi magamit ang mensahe o download',
        intro:
          'Nakaugnay sa karapat-dapat na kalakalan ang chat, at maaaring maghintay sa pagbuo ng ulat.',
        steps: {
          follow: {
            instruction:
              'Tingnan kung may kaukulang purchase o tinanggap na demand offer bago mag-chat. Kapag nabigong mag-load ang mensahe o dokumento, gamitin ang retry o download kapag magagamit na.',
            result:
              'Mabubuksan ang karapat-dapat na pag-uusap at handang dokumento. Hindi maaaring mag-chat nang walang kalakalan sa bawat profile.',
          },
        },
        notes: {},
        recovery: {},
      },
      'contact-support': {
        title: 'Humingi ng tulong',
        intro: 'Magbigay ng sapat na detalye para maunawaan ang problema.',
        steps: {
          follow: {
            instruction:
              'Buksan ang Contact sa site o Report Issue sa dashboard. Ilarawan ang sinubukan, pahina at error. Suriin ang mensahe bago kumpirmahin ang pagsusumite.',
            result:
              'Matatanggap ng team ang inquiry o issue record. Huwag isama ang password, payment credentials at pribadong verification link.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
}
