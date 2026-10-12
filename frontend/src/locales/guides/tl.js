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
  'farms-and-plots': {
    title: 'Bukid at mga plot',
    summary: 'Ayusin ang taniman at i-save ang hangganan ng plot kasama ang impormasyon ng lupa.',
    prerequisites: {
      prepare: 'Gumamit ng Magsasaka account na may beripikadong email para mag-save ng tala.',
    },
    sections: {
      'create-farm': {
        title: 'Gumawa ng bukid',
        intro: 'Pinagsasama ng bukid ang mga plot sa iisang pangalan.',
        steps: {
          follow: {
            instruction:
              'Buksan ang Aking mga Bukid at piliing gumawa ng bukid. Ilagay ang pangalan at lokasyon o kabuuang lawak kung alam. Suriin ang form at kumpirmahin ang paggawa.',
            result:
              'Lilitaw ang bukid sa Aking mga Bukid. Kailangan ang pangalan; opsyonal ang address, lungsod at iba pang lokasyon.',
          },
        },
        notes: {},
        recovery: {},
      },
      'draw-a-plot': {
        title: 'Iguhit ang hangganan ng plot',
        intro: 'Mapa ang ginagamit ng plot planner sa pagtatala ng taniman.',
        steps: {
          follow: {
            instruction:
              'Buksan ang pamamahala ng plot ng bukid. Hanapin ang tamang lugar gamit ang search at zoom. Piliin ang polygon tool, ilagay ang mga punto ng hangganan at tapusin ang hugis.',
            result:
              'Lilitaw ang hindi pa naka-save na hangganan at form ng plot. Suriin ang lugar at hugis bago mag-save.',
          },
        },
        notes: {},
        recovery: {},
      },
      'save-a-plot': {
        title: 'Suriin at i-save ang plot',
        intro: 'Kinakalkula ang lawak mula sa iginuhit na hangganan.',
        steps: {
          follow: {
            instruction:
              'Ilagay ang pangalan at piliin ang uri ng lupang tumutugma sa lugar. Suriin ang lawak, mag-save at kumpirmahin. Kung mali ang drawing, kanselahin ang hindi pa naka-save na form at gumuhit muli.',
            result:
              'Mapapasama ang plot sa bukid at magagamit sa pagsusuri. Walang pag-edit o pagbura ng naka-save na hangganan sa kasalukuyang planner.',
          },
        },
        notes: {},
        recovery: {},
      },
      'map-problems': {
        title: 'Ayusin ang problema sa mapa',
        intro: 'Maaaring tanggihan ang drawing sa restricted area o labas ng natukoy na lungsod.',
        steps: {
          follow: {
            instruction:
              'Basahin ang dahilan, tingnan ang lokasyon ng bukid at iguhit muli ang hindi pa naka-save na polygon kung angkop. Kung hindi malutas, makipag-ugnayan sa suporta gamit ang pangalan at error.',
            result:
              'Maaaring suriin muli ang inayos na drawing. Pantulong sa pagpaplano ang mapa at lawak, hindi legal na land survey.',
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
      'Humiling ng mungkahing pananim para sa plot, ihambing ang mga pagpipilian at piliing maglathala ng hinaharap na ani.',
    prerequisites: {
      prepare:
        'Gumawa muna ng plot. Itala nang maayos ang lupa at lokasyon dahil batayan ito ng mga mungkahi.',
    },
    sections: {
      'request-analysis': {
        title: 'Humiling ng pagsusuri',
        intro: 'Nakaugnay sa naka-save na plot ang pagsusuri.',
        steps: {
          follow: {
            instruction:
              'Buksan ang Mga Rekomendasyon, pumili ng plot at tingnan ang lupa at lokasyon. Itakda ang produce type, subtype, patubig at layunin kung kailangan. Piliin ang pagsusuri at kumpirmahin ang hiling.',
            result:
              'Lilitaw ang processing state. Tumatakbo sa background ang pagsusuri; hintayin ang resulta sa halip na paulit-ulit na magsumite.',
          },
        },
        notes: {},
        recovery: {},
      },
      'read-results': {
        title: 'Basahin ang resulta at pagtataya',
        intro: 'Ipinapaliwanag ang pagiging angkop ng pananim batay sa mga input at impormasyon.',
        steps: {
          follow: {
            instruction:
              'Basahin ang dahilan, confidence at tinatayang ani o halaga sa crop card. Isaalang-alang ang weather context at sariling kondisyon sa bukid. Gamitin ang retry kung mabigo ang pagsusuri.',
            result:
              'May mga pagpipiliang maihahambing. Hindi garantiya ng ani, kita o panahon ang pagtataya at confidence ng AI; kumonsulta rin sa lokal na agricultural guidance.',
          },
        },
        notes: {},
        recovery: {},
      },
      'publish-a-contract': {
        title: 'Maglathala ng kontrata',
        intro: 'Magkaibang desisyon ang pagsusuri at paglalathala.',
        steps: {
          follow: {
            instruction:
              'Piliin ang Tanggapin at Ikontrata sa karapat-dapat na rekomendasyon. Suriin ang dami, presyo kada kg, petsa ng ani at iba pang termino, saka kumpirmahin ang paglalathala.',
            result:
              'Lilitaw sa Aking mga Kontrata ang kontrata. Hindi muling mailalathala ang na-publish o tinanggihang rekomendasyon; hindi awtomatikong listing ang pagsusuri.',
          },
        },
        notes: {},
        recovery: {},
      },
      'compare-crops': {
        title: 'Ihambing ang mga pananim',
        intro: 'Hiwalay na tool ang compatibility.',
        steps: {
          follow: {
            instruction:
              'Buksan ang Compatibility, piliin ang dalawang pananim at gamitin ang paghahambing. Basahin ang paliwanag bago pumili ng rotation o intercropping.',
            result:
              'Maaaring ihambing kahit walang listing o natapos na pagsusuri. Sinusuportahan nito ang pagpapasya at hindi pinapalitan ang payo sa bukid.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'selling-harvests': {
    title: 'Pagbebenta ng kasalukuyang ani',
    summary:
      'Direktang mag-post ng manual harvest listing, may kaugnay na bukid o mungkahi ng AI man o wala.',
    prerequisites: {
      prepare:
        'Beripikahin ang email ng Magsasaka account. Ihanda ang tumpak na pananim, dami, presyo at availability.',
    },
    sections: {
      'manual-listing': {
        title: 'Ilagay ang detalye ng ani',
        intro: 'Direktang landas sa pagbebenta ang manual harvest.',
        steps: {
          follow: {
            instruction:
              'Buksan ang Post Harvest mula sa Aking mga Kontrata. Piliin ang pananim, shelf life at pamagat; ilagay ang paglalarawan, dami sa kg at presyo kada kg. Markahang available ngayon o ilagay ang inaasahang petsa ng ani.',
            result:
              'Inilalarawan ng draft ang mabibili ng mamimili. Sundin ang mga limitasyon at ilagay ang dami at petsang maibibigay mo.',
          },
        },
        notes: {},
        recovery: {},
      },
      'optional-source': {
        title: 'Iugnay ang pinagmulan kung kailangan',
        intro: 'Opsyonal ang Harvest Source.',
        steps: {
          follow: {
            instruction:
              'Iwanang walang laman kung walang farm record, o piliin ang sariling bukid at plot nito. Nawawala ang dating napiling plot kapag pinalitan ang bukid.',
            result:
              'Maaaring mag-post ng manual harvest kahit walang bukid, drawing o mungkahi ng AI. Nagbibigay ng konteksto ang source ngunit hindi gumagawa ng verification badge.',
          },
        },
        notes: {},
        recovery: {},
      },
      'review-and-publish': {
        title: 'Suriin at ilathala',
        intro: 'Makatutulong ang price context sa pagsusuri ng draft.',
        steps: {
          follow: {
            instruction:
              'Gamitin ang Price Guide kung kailangan at tingnan ang source at petsa. Suriin ang pananim, dami, presyo, availability at pinagmulan. Isumite at kumpirmahin lamang kapag tama ang detalye.',
            result:
              'Ibabalik ka sa Aking mga Kontrata kapag matagumpay. Kung mabigo, basahin ang error at ayusin o subukang muli; hindi garantiya ng benta ang reference price.',
          },
        },
        notes: {},
        recovery: {},
      },
      'manage-listings': {
        title: 'Subaybayan ang benta at resibo',
        intro: 'Magkasama sa Aking mga Kontrata ang listing at contract records.',
        steps: {
          follow: {
            instruction:
              'Suriin ang status at mga control ng listing. Sa karapat-dapat na kalakalan, mag-message para mag-coordinate. Sa cash purchase, buksan ang Cash Approvals at itala lang kapag natanggap na ang bayad.',
            result:
              'Hiwalay ang pending cash request sa natanggap na pera. Sundin ang mga control ng pagkansela o kumpirmasyon kung magagamit.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'verification-and-trust-score': {
    title: 'Beripikasyon at trust score',
    summary: 'Unawain ang provenance badge at score batay sa mga gawain sa farmer workspace.',
    prerequisites: {
      prepare:
        'Magkaiba ang farm badge at email verification. Walang badge o score na garantiya ng kalakalan o pautang.',
    },
    sections: {
      'email-and-farm-verification': {
        title: 'Unawain ang verification badge',
        intro: 'Magkaiba ang email at farm verification.',
        steps: {
          follow: {
            instruction:
              'Beripikahin ang email sa link para sa protektadong gawain. Basahin ang farm at plot status bilang pagsusuri ng admin sa pinagmulan; kailangang beripikado ang bukid at plot para sa plot badge.',
            result:
              'Hindi hinaharang ng pending farm review ang paggawa, pagsusuri at manual listing. Hindi garantiya ng ani, delivery o bayad ang badge.',
          },
        },
        notes: {},
        recovery: {},
      },
      'read-your-score': {
        title: 'Unawain ang farmer trust score',
        intro: 'Binubuod ng score ang mga gawaing naitala sa YieldGrid.',
        steps: {
          follow: {
            instruction:
              'Buksan ang Farmer Trust Score at tingnan ang breakdown, history at improvement tips. Isaalang-alang ang plot, rekomendasyon, kontrata, offer, transaction at tagal sa platform.',
            result:
              'Makikita ang ambag sa internal score. Hindi ito bank credit decision, loan approval o garantiya ng kakayahan sa aktuwal na pagsasaka.',
          },
        },
        notes: {},
        recovery: {},
      },
      'download-report': {
        title: 'Gumawa ng maibabahaging ulat',
        intro: 'Binubuo ang ulat mula sa impormasyon ng score.',
        steps: {
          follow: {
            instruction:
              'Piliin ang pagbuo ng ulat, suriin at kumpirmahin, saka hintaying maging handa bago mag-download ng PDF. Sundin ang mensahe kung may limitasyon o expiry bago gumawa muli.',
            result:
              'Maaaring ibahagi ang ulat kung kapaki-pakinabang. Walang garantiya na tatanggapin ito ng lender o magkakaroon ng pautang.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'insurance-and-rsbsa': {
    title: 'Crop insurance at RSBSA',
    summary: 'Ihanda ang dokumento ng enrollment at ayusin ang tala ng policy, claim at paalala.',
    prerequisites: {
      prepare:
        'Gumamit ng Magsasaka account at plot sa kasalukuyang enrollment form. Kumpirmahin ang eligibility at deadline sa lokal na agriculture office o PCIC.',
    },
    sections: {
      'rsbsa-records': {
        title: 'Itala ang RSBSA',
        intro: 'Iniimbak ng app ang detalyeng ibinibigay mo.',
        steps: {
          follow: {
            instruction:
              'Buksan ang Crop Insurance at ilagay ang tamang impormasyon at status ng RSBSA. Suriin at kumpirmahin; kontakin ang agriculture office para sa opisyal na registration o pagwawasto.',
            result:
              'Makatutulong ang tala sa dokumento. Hindi beripikasyon o opisyal na RSBSA registration ang pag-save ng numero sa YieldGrid.',
          },
        },
        notes: {},
        recovery: {},
      },
      'prepare-enrollment': {
        title: 'Ihanda ang enrollment record',
        intro: 'May rice/corn program at wet/dry season sa kasalukuyang gabay.',
        steps: {
          follow: {
            instruction:
              'Piliin ang plot, programa, season at taon. Suriin at kumpirmahin ang paggawa ng record. Alamin ang dokumentong kailangan sa lokal na opisina bago ituring na application ang tala.',
            result:
              'May enrollment preparation record. Hindi ito bumibili ng coverage o awtomatikong nagpapatunay ng eligibility.',
          },
        },
        notes: {},
        recovery: {},
      },
      'download-and-submit': {
        title: 'I-print at isumite sa labas ng app',
        intro: 'Sa labas ng YieldGrid ang opisyal na pagsusumite.',
        steps: {
          follow: {
            instruction:
              'Bumuo ng enrollment pack sa kumpirmasyon at i-download kapag handa. I-print at suriin ang form at attachments, saka isumite sa Municipal Agriculture Office ayon sa tagubilin. Itala lamang ang submitted status matapos magsumite.',
            result:
              'Pantulong sa paghahanda ang pack. Hindi ka awtomatikong ine-enroll ng YieldGrid sa PCIC at hindi nito kinukumpirma ang pagtanggap ng application.',
          },
        },
        notes: {},
        recovery: {},
      },
      'policy-and-claims': {
        title: 'Subaybayan ang policy at claim',
        intro: 'Batay sa impormasyong inilalagay matapos ang panlabas na hakbang ang mga tala.',
        steps: {
          follow: {
            instruction:
              'Kapag naibigay na sa labas, itala ang tamang CIC/policy. Idagdag at baguhin ang claim record ayon lamang sa aktuwal na submission at desisyon; suriin ang paalala at kumpirmahin bago mag-save.',
            result:
              'Maayos ang tala ng dokumento at petsa. Insurer o lokal na opisina ang nagpapasya sa coverage, approval at bayad; hindi ang pagbabago ng status sa app.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
}
