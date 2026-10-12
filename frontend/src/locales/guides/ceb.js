export const GUIDE_UI = {
  title: 'Dokumentasyon sa YieldGrid',
  eyebrow: 'Kauban sa pagpanguma',
  intro:
    'Praktikal nga mga giya sa pagplano sa tanom, pagbaligya sa ani, ug paggamit sa YieldGrid. Pagpili og hilisgutan, sunda ang mga lakang, ug balik kon kinahanglan og tabang.',
  topics: 'Mga hilisgutan sa giya',
  on_page: 'Niining panid',
  before: 'Sa dili pa magsugod',
  result: 'Gipaabot nga resulta:',
  if_stuck: 'Kon adunay problema',
  next: 'May kalabotang mga giya',
  contact: 'Kinahanglan og tabang? Kontaka ang team',
  back: 'Balik sa dokumentasyon',
  not_found: 'Wala makit-i ang giya',
  not_found_desc:
    'Walay kasamtangang hilisgutan niining adres. Pagpili sa mga hilisgutan o balik sa dokumentasyon.',
  groups: {
    start: 'Sugdi dinhi',
    grow: 'Pagplano ug pagtanom',
    trade: 'Patigayon ug koneksiyon',
    support: 'Suporta sa uma',
  },
  audiences: {
    both: 'Para sa mag-uuma ug mamalitay',
    farmer: 'Para sa mag-uuma',
    buyer: 'Para sa mamalitay',
  },
}

export default {
  'getting-started': {
    title: 'Pagsugod',
    summary: 'Paghimo sa hustong account, pamatud-i ang email, ug pilia ang unang buluhaton.',
    prerequisites: {
      prepare:
        'Kinahanglan ang email nga imong maablihan. Gamita ang kaugalingong account ug ayaw ipaambit ang password.',
    },
    sections: {
      'choose-role': {
        title: 'Pilia ang imong papel',
        intro: 'Ang papel nagbuot sa workspace ug mga buluhaton nga magamit.',
        steps: {
          follow: {
            instruction:
              'Pilia ang Mag-uuma kon magplano o magbaligya ka sa ani. Pilia ang Mamalitay kon mopalit o mopaskil ka sa gikinahanglang tanom.',
            result: 'Ang dashboard ug nabigasyon motakdo sa gipiling papel sa pagparehistro.',
          },
        },
        notes: {},
        recovery: {},
      },
      register: {
        title: 'Paghimo og account',
        intro: 'Sugdi sa Get Started sa publikong site.',
        steps: {
          follow: {
            instruction:
              'Ablihi ang pagparehistro. Isulod ang ngalan, email, password ug susamang kumpirmasyon, ug pagpili og papel. Susihon ang detalye ug sundon ang kumpirmasyon sa paghimo og account.',
            result:
              'Naka-sign in ka sa dashboard sa imong papel. Opsyonal ang adres sa pagparehistro ug mahimo kining idugang unya.',
          },
        },
        notes: {},
        recovery: {},
      },
      'verify-email': {
        title: 'Pamatud-i ang email',
        intro:
          'Ang email verification moabli sa protektadong mga buluhaton. Lahi kini sa pagsusi sa admin sa uma.',
        steps: {
          follow: {
            instruction:
              'Ablihi ang mensahe sa verification ug sunda ang link. Kon expired o wala kini, gamita ang banner aron ipadala pag-usab human sa gipakitang paghulat. Susiha usab ang spam.',
            result:
              'Ang balidong link mopamatud sa email. Dili kinahanglan maghulat sa farm verification badge aron maghimo og uma, mosusi sa tanom o mopaskil og manual nga ani.',
          },
        },
        notes: {},
        recovery: {},
      },
      'sign-in-and-recover': {
        title: 'Pag-sign in o ibalik ang access',
        intro: 'Gamita ang email nga imong giparehistro.',
        steps: {
          follow: {
            instruction:
              'Ablihi ang Log in ug isulod ang email ug password. Kon nalimtan ang password, pilia ang pagkuha pag-usab niini, pangayo og reset email ug sunda ang link sa bag-ong password.',
            result:
              'Ang malampusong pag-sign in mobalik kanimo sa workspace. Kinahanglan ang access sa rehistradong email aron mabawi ang account.',
          },
        },
        notes: {},
        recovery: {},
      },
      'next-steps': {
        title: 'Pilia ang unang buluhaton',
        intro: 'Adunay labaw sa usa ka paagi sa pagsugod sa YieldGrid.',
        steps: {
          follow: {
            instruction:
              'Ang mag-uuma mahimong maghimo og uma alang sa pagplano o direktang mopaskil sa ani. Ang mamalitay mahimong motan-aw sa marketplace o mopaskil og demand. Basaha ang giya sa dili pa mokumpirma.',
            result:
              'Lahi nga mga agianan ang pagplano, direktang pagbaligya ug buyer demand. Matun-an nimo ang ubang tool kon gikinahanglan na.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'profile-addresses-language': {
    title: 'Profile, adres ug pinulongan',
    summary:
      'Pangitaa ang kaugalingong profile, tipigi ang marketplace nga adres ug gamita ang YieldGrid sa gusto nimong pinulongan.',
    prerequisites: {
      prepare: 'Pag-sign in aron madumala ang profile ug natipig nga mga adres.',
    },
    sections: {
      'open-profile': {
        title: 'Ablihi ang kaugalingong profile',
        intro: 'Adunay profile ang Mag-uuma ug Mamalitay.',
        steps: {
          follow: {
            instruction:
              'Ablihi ang profile sa nabigasyon sa dashboard. Sa telepono, ablihi ang navigation drawer ug pilia ang kaugalingong ngalan o profile.',
            result:
              'Masusi nimo ang account ug madumala ang mga adres gikan sa kaugalingong profile.',
          },
        },
        notes: {},
        recovery: {},
      },
      'saved-addresses': {
        title: 'Tipigi ang adres',
        intro: 'Ang ubang buluhaton sa marketplace nagkinahanglan og natipig nga adres.',
        steps: {
          follow: {
            instruction:
              'Ablihi ang pagdumala sa adres sa kaugalingong profile. Isulod ang gikinahanglang contact ug lokasyon, susiha ug kumpirmaha ang pagtipig. Susiha ang napiling adres sa dili pa mopaskil og demand o mosumite og offer.',
            result:
              'Magamit ang natipig nga adres sa mga katugbang nga porma. Dili kini kinahanglan sa paghimo sa account, apan kinahanglan sa pagpaskil sa demand ug pagsumite sa offer.',
          },
        },
        notes: {},
        recovery: {},
      },
      'change-language': {
        title: 'Usba ang pinulongan',
        intro: 'Adunay English, Tagalog ug Cebuano sa app.',
        steps: {
          follow: {
            instruction:
              'Gamita ang language control sa header o dashboard. Sa telepono, ablihi ang menu o navigation drawer aron makita kini. Pilia ang gusto nimong pinulongan.',
            result:
              'Mausab ang teksto nga dili mausab ang imong gitan-aw nga buluhaton. Mahinumdoman ang pinili kon magamit ang storage; mahimo usab kini matipig sa naka-sign in nga account.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'troubleshooting-and-support': {
    title: 'Mga problema ug suporta',
    summary:
      'Pangitaa ang sunod nga lakang kon dili molihok sumala sa gipaabot ang account, mapa, pagsusi o patigayon.',
    prerequisites: {
      prepare:
        'Tipigi ang mensahe sa sayop ug detalye sa buluhaton. Ayaw ipaambit ang password o verification link.',
    },
    sections: {
      'account-access': {
        title: 'Problema sa account o verification',
        intro: 'Susiha una unsang account ug email ang gigamit.',
        steps: {
          follow: {
            instruction:
              'Susiha ang email ug spam, ug ipadala pag-usab ang verification message gikan sa banner human sa paghulat. Gamita ang password recovery kon nalimtan ang password.',
            result:
              'Ang bag-ong verification link o password reset makatabang sa pagbalik sa access. Lahi ang email verification sa farm review badge.',
          },
        },
        notes: {},
        recovery: {},
      },
      'farm-and-analysis': {
        title: 'Problema sa mapa o pagsusi',
        intro:
          'Susihon ang gisalikway nga drawing o napakyas nga pagsusi sa dili pa mosumite pag-usab.',
        steps: {
          follow: {
            instruction:
              'Basaha ang hinungdan. Usba ang drawing nga mosapaw sa restricted area o naa sa gawas sa nasulbad nga utlanan sa siyudad. Sa napakyas nga pagsusi, gamita ang retry human masusi ang plot ug mga input.',
            result:
              'Adunay laing pagsulay sa porma o retry. Ang pagsusi dili dayon mahuman ug dili kini awtomatikong mopatik og kontrata.',
          },
        },
        notes: {},
        recovery: {},
      },
      'trading-and-payments': {
        title: 'Problema sa patigayon o bayad',
        intro: 'Ang pending dili pasabot nga nadawat na ang bayad.',
        steps: {
          follow: {
            instruction:
              'Susiha ang purchase o offer status ug sayop sa dili pa mosulay pag-usab. Pagdugang og adres kon gikinahanglan sa porma. Tipigi ang draft ug kumpirmaha lang ang cash receipt human madawat ang kuwarta.',
            result:
              'Mahibal-an kon adunay kulang o giproseso pa ang bayad. Ang gidawat nga offer gigahin, apan dili awtomatikong bayad.',
          },
        },
        notes: {},
        recovery: {},
      },
      'messages-and-downloads': {
        title: 'Dili magamit ang mensahe o download',
        intro:
          'Ang chat nalambigit sa kwalipikadong patigayon, ug ang report mahimong magkinahanglan og panahon.',
        steps: {
          follow: {
            instruction:
              'Susiha kon adunay kwalipikadong purchase o gidawat nga demand offer sa dili pa mag-chat. Kon mapakyas ang mensahe o dokumento, gamita ang retry o download kon magamit na.',
            result:
              'Maablihan ang kwalipikadong panag-istorya ug andam nga dokumento. Dili makig-chat nga walay patigayon sa tanang profile.',
          },
        },
        notes: {},
        recovery: {},
      },
      'contact-support': {
        title: 'Pangayo og tabang',
        intro: 'Ihatag ang igo nga detalye aron masabtan ang problema.',
        steps: {
          follow: {
            instruction:
              'Ablihi ang Contact sa site o Report Issue sa dashboard. Ihulagway ang gisulayan, panid ug sayop. Susiha ang mensahe sa dili pa mokumpirma sa pagsumite.',
            result:
              'Madawat sa team ang inquiry o issue record. Ayaw iapil ang password, payment credentials ug pribadong verification link.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
}
