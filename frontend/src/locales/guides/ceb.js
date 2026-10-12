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
  'farms-and-plots': {
    title: 'Uma ug mga plot',
    summary: 'Organisaha ang tanoman ug tipigi ang utlanan sa plot uban sa impormasyon sa yuta.',
    prerequisites: {
      prepare:
        'Gamita ang Mag-uuma account nga napamatud-an ang email aron matipig ang mga rekord.',
    },
    sections: {
      'create-farm': {
        title: 'Paghimo og uma',
        intro: 'Ang uma naggrupo sa mga plot ilalom sa usa ka ngalan.',
        steps: {
          follow: {
            instruction:
              'Ablihi ang Akong mga Uma ug pilia ang paghimo og uma. Isulod ang ngalan ug lokasyon o kinatibuk-ang gilapdon kon nahibaloan. Susiha ug kumpirmaha ang porma.',
            result:
              'Makita ang uma sa Akong mga Uma. Gikinahanglan ang ngalan; opsyonal ang adres, siyudad ug ubang lokasyon.',
          },
        },
        notes: {},
        recovery: {},
      },
      'draw-a-plot': {
        title: 'Idrowing ang utlanan sa plot',
        intro: 'Mapa ang gigamit sa plot planner aron itala ang tanoman.',
        steps: {
          follow: {
            instruction:
              'Ablihi ang pagdumala sa plot sa uma. Pangitaa ang hustong dapit gamit ang search ug zoom. Pilia ang polygon tool, ibutang ang mga punto sa utlanan ug taposa ang porma.',
            result:
              'Makita ang wala pa matipig nga utlanan ug ang porma sa plot. Susiha ang dapit ug porma sa dili pa tipigan.',
          },
        },
        notes: {},
        recovery: {},
      },
      'save-a-plot': {
        title: 'Susiha ug tipigi ang plot',
        intro: 'Gikuwenta ang gilapdon gikan sa gidrowing nga utlanan.',
        steps: {
          follow: {
            instruction:
              'Isulod ang ngalan ug pilia ang klase sa yuta nga motakdo sa dapit. Susiha ang gilapdon, tipigi ug kumpirmaha. Kon sayop ang drawing, kanselahon ang wala pa matipig nga porma ug pagdrowing pag-usab.',
            result:
              'Mahisakop ang plot sa uma ug magamit sa pagsusi. Walay pag-edit o pagtangtang sa natipig nga utlanan sa kasamtangang planner.',
          },
        },
        notes: {},
        recovery: {},
      },
      'map-problems': {
        title: 'Sulbara ang problema sa mapa',
        intro: 'Mahimong isalikway ang drawing sa restricted area o gawas sa nasulbad nga siyudad.',
        steps: {
          follow: {
            instruction:
              'Basaha ang hinungdan, susiha ang lokasyon ug usba ang wala pa matipig nga polygon kon angay. Kon dili masulbad, kontaka ang suporta uban sa ngalan ug mensahe sa sayop.',
            result:
              'Masusi pag-usab ang gitul-id nga drawing. Ang mapa ug gilapdon tabang sa pagplano, dili legal nga land survey.',
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
      'Pangayo og mga sugyot sa tanom alang sa plot, itandi ang mga kapilian ug pilia kon ipatik ang umaabot nga ani.',
    prerequisites: {
      prepare:
        'Paghimo una og plot. Itala pag-ayo ang yuta ug lokasyon kay mao kini ang basehan sa mga sugyot.',
    },
    sections: {
      'request-analysis': {
        title: 'Pangayo og pagsusi',
        intro: 'Ang pagsusi nalambigit sa natipig nga plot.',
        steps: {
          follow: {
            instruction:
              'Ablihi ang Mga Rekomendasyon, pagpili og plot ug susiha ang yuta ug lokasyon. Itakda ang produce type, subtype, irigasyon ug tumong kon gikinahanglan. Pilia ang pagsusi ug kumpirmaha ang hangyo.',
            result:
              'Makita ang processing state. Nagdagan sa background ang pagsusi; hulata ang resulta imbes sige og submit.',
          },
        },
        notes: {},
        recovery: {},
      },
      'read-results': {
        title: 'Basaha ang resulta ug banabana',
        intro: 'Gipasabot ang pagkaangay sa tanom gikan sa mga input ug impormasyon.',
        steps: {
          follow: {
            instruction:
              'Basaha ang hinungdan, confidence ug gibanabanang ani o kantidad sa crop card. Hunahunaa ang weather context ug kaugalingong kahimtang sa uma. Gamita ang retry kon mapakyas ang pagsusi.',
            result:
              'Adunay mga kapilian nga matandi. Dili garantiya sa ani, kita o panahon ang banabana ug AI confidence; konsultaha usab ang lokal nga agricultural guidance.',
          },
        },
        notes: {},
        recovery: {},
      },
      'publish-a-contract': {
        title: 'Ipatik ang kontrata',
        intro: 'Lahi nga desisyon ang pagsusi ug pagpatik.',
        steps: {
          follow: {
            instruction:
              'Pilia ang Dawata ug Ikontrata sa kwalipikadong rekomendasyon. Susiha ang gidaghanon, presyo kada kg, petsa sa ani ug ubang termino, dayon kumpirmaha ang pagpatik.',
            result:
              'Makita ang kontrata sa Akong mga Kontrata. Dili mapatik pag-usab ang na-publish o gisalikway nga rekomendasyon; dili awtomatikong listing ang pagsusi.',
          },
        },
        notes: {},
        recovery: {},
      },
      'compare-crops': {
        title: 'Itandi ang mga tanom',
        intro: 'Lahi nga tool ang compatibility.',
        steps: {
          follow: {
            instruction:
              'Ablihi ang Compatibility, pilia ang duha ka tanom ug gamita ang pagtandi. Basaha ang pasabot sa dili pa mopili og rotation o intercropping.',
            result:
              'Matandi bisan walay listing o nahuman nga pagsusi. Kini mosuporta sa pagdesisyon ug dili mopuli sa tambag sa uma.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'selling-harvests': {
    title: 'Pagbaligya sa kasamtangang ani',
    summary:
      'Direktang pagpaskil og manual harvest listing, adunay nalambigit nga uma o sugyot sa AI o wala.',
    prerequisites: {
      prepare:
        'Pamatud-i ang email sa Mag-uuma account. Andama ang tukmang tanom, gidaghanon, presyo ug availability.',
    },
    sections: {
      'manual-listing': {
        title: 'Isulod ang detalye sa ani',
        intro: 'Direktang agianan sa pagbaligya ang manual harvest.',
        steps: {
          follow: {
            instruction:
              'Ablihi ang Post Harvest gikan sa Akong mga Kontrata. Pilia ang tanom, shelf life ug titulo; isulod ang paghulagway, gidaghanon sa kg ug presyo kada kg. Markahi nga available karon o isulod ang gipaabot nga petsa sa ani.',
            result:
              'Ang draft naghulagway sa mapalit sa mamalitay. Sunda ang mga limitasyon ug isulod ang gidaghanon ug petsa nga matuman nimo.',
          },
        },
        notes: {},
        recovery: {},
      },
      'optional-source': {
        title: 'Ilambigit ang tinubdan kon mapuslanon',
        intro: 'Opsyonal ang Harvest Source.',
        steps: {
          follow: {
            instruction:
              'Biyai nga walay sulod kon walay farm record, o pilia ang kaugalingong uma ug plot niini. Matangtang ang daang plot kon usbon ang uma.',
            result:
              'Makapaskil og manual harvest bisan walay uma, drawing o sugyot sa AI. Ang tinubdan naghatag og konteksto apan dili maghimo og verification badge.',
          },
        },
        notes: {},
        recovery: {},
      },
      'review-and-publish': {
        title: 'Susiha ug ipatik',
        intro: 'Ang price context makatabang sa pagsusi sa draft.',
        steps: {
          follow: {
            instruction:
              'Gamita ang Price Guide kon gikinahanglan ug susiha ang tinubdan ug petsa. Susiha ang tanom, gidaghanon, presyo, availability ug tinubdan. Isumite ug kumpirmaha lamang kon husto ang detalye.',
            result:
              'Mobalik ka sa Akong mga Kontrata kon malampuson. Kon mapakyas, basaha ang sayop ug tul-ira o sulayi pag-usab; dili garantiya sa halin ang reference price.',
          },
        },
        notes: {},
        recovery: {},
      },
      'manage-listings': {
        title: 'Sunda ang halin ug resibo',
        intro: 'Magkauban sa Akong mga Kontrata ang listing ug contract records.',
        steps: {
          follow: {
            instruction:
              'Susiha ang status ug mga control sa listing. Sa kwalipikadong patigayon, pag-message aron mag-coordinate. Sa cash purchase, ablihi ang Cash Approvals ug itala lamang human madawat ang bayad.',
            result:
              'Lahi ang pending cash request ug nadawat nga kuwarta. Sunda ang mga control sa pagkansela o kumpirmasyon kon magamit.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'verification-and-trust-score': {
    title: 'Verification ug trust score',
    summary: 'Sabta ang provenance badge ug score nga gibase sa buluhaton sa farmer workspace.',
    prerequisites: {
      prepare:
        'Lahi ang farm badge ug email verification. Walay badge o score nga garantiya sa patigayon o pautang.',
    },
    sections: {
      'email-and-farm-verification': {
        title: 'Sabta ang verification badge',
        intro: 'Lahi ang email ug farm verification.',
        steps: {
          follow: {
            instruction:
              'Pamatud-i ang email sa link alang sa protektadong buluhaton. Basaha ang farm ug plot status isip pagsusi sa admin sa tinubdan; kinahanglan verified ang uma ug plot alang sa plot badge.',
            result:
              'Ang pending farm review dili mobabag sa paghimo, pagsusi ug manual listing. Dili garantiya sa ani, delivery o bayad ang badge.',
          },
        },
        notes: {},
        recovery: {},
      },
      'read-your-score': {
        title: 'Sabta ang farmer trust score',
        intro: 'Ang score nagsumada sa buluhaton nga natala sa YieldGrid.',
        steps: {
          follow: {
            instruction:
              'Ablihi ang Farmer Trust Score ug tan-awa ang breakdown, history ug improvement tips. Hunahunaa ang plot, rekomendasyon, kontrata, offer, transaction ug kadugayon sa platform.',
            result:
              'Makita ang mga kontribusyon sa internal score. Dili kini bank credit decision, loan approval o garantiya sa abilidad sa aktuwal nga pagpanguma.',
          },
        },
        notes: {},
        recovery: {},
      },
      'download-report': {
        title: 'Paghimo og mapaambit nga report',
        intro: 'Ang report gihimo gikan sa impormasyon sa score.',
        steps: {
          follow: {
            instruction:
              'Pilia ang paghimo sa report, susiha ug kumpirmaha, dayon hulata nga andam sa dili pa i-download ang PDF. Sunda ang mensahe kon adunay limitasyon o expiry sa dili pa maghimo pag-usab.',
            result:
              'Mapaambit ang report kon mapuslanon. Walay garantiya nga dawaton kini sa lender o adunay pautang.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'insurance-and-rsbsa': {
    title: 'Crop insurance ug RSBSA',
    summary: 'Andama ang dokumento sa enrollment ug organisaha ang policy, claim ug pahinumdom.',
    prerequisites: {
      prepare:
        'Gamita ang Mag-uuma account ug plot sa kasamtangang enrollment form. Kumpirmaha ang eligibility ug deadline sa lokal nga agriculture office o PCIC.',
    },
    sections: {
      'rsbsa-records': {
        title: 'Itala ang RSBSA',
        intro: 'Gitipigan sa app ang detalye nga imong gihatag.',
        steps: {
          follow: {
            instruction:
              'Ablihi ang Crop Insurance ug isulod ang hustong impormasyon ug status sa RSBSA. Susiha ug kumpirmaha; kontaka ang agriculture office alang sa opisyal nga registration o pagtul-id.',
            result:
              'Makatabang ang rekord sa dokumento. Dili verification o opisyal nga RSBSA registration ang pagtipig sa numero sa YieldGrid.',
          },
        },
        notes: {},
        recovery: {},
      },
      'prepare-enrollment': {
        title: 'Andama ang enrollment record',
        intro: 'Adunay rice/corn program ug wet/dry season sa kasamtangang giya.',
        steps: {
          follow: {
            instruction:
              'Pilia ang plot, programa, season ug tuig. Susiha ug kumpirmaha ang paghimo sa record. Pangutan-a ang lokal nga opisina sa gikinahanglang dokumento sa dili pa isipon nga application ang rekord.',
            result:
              'Adunay enrollment preparation record. Dili kini mopalit og coverage o awtomatikong mopamatud sa eligibility.',
          },
        },
        notes: {},
        recovery: {},
      },
      'download-and-submit': {
        title: 'I-print ug isumite sa gawas sa app',
        intro: 'Sa gawas sa YieldGrid ang opisyal nga pagsumite.',
        steps: {
          follow: {
            instruction:
              'Himoa ang enrollment pack pinaagi sa kumpirmasyon ug i-download kon andam. I-print ug susiha ang porma ug attachments, dayon isumite sa Municipal Agriculture Office sumala sa instruksyon. Itala lamang ang submitted status human mosumite.',
            result:
              'Tabang sa pag-andam ang pack. Dili awtomatikong mo-enroll ang YieldGrid kanimo sa PCIC o mokumpirma sa pagdawat sa application.',
          },
        },
        notes: {},
        recovery: {},
      },
      'policy-and-claims': {
        title: 'Sunda ang policy ug claim',
        intro: 'Gibase ang mga rekord sa imong gisulod human sa mga lakang sa gawas.',
        steps: {
          follow: {
            instruction:
              'Kon na-isyu na sa gawas, itala ang hustong CIC/policy. Idugang ug usba ang claim record sumala lamang sa aktuwal nga submission ug desisyon; susiha ang pahinumdom ug kumpirmaha sa dili pa tipigan.',
            result:
              'Organisado ang rekord sa dokumento ug petsa. Ang insurer o lokal nga opisina nagbuot sa coverage, approval ug bayad; dili ang pag-usab sa status sa app.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
}
