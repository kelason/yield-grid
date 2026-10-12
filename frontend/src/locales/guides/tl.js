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
        intro: 'Magsimula sa Magsimula ng pampublikong site.',
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
              'Buksan ang Mag-log in at ilagay ang email at password. Kung nakalimutan ang password, piliin ang pagbawi nito, humiling ng reset email at sundin ang link para sa bagong password.',
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
              'Buksan ang Kontak sa site o Mag-ulat ng Isyu sa dashboard. Ilarawan ang sinubukan, pahina at error. Suriin ang mensahe bago kumpirmahin ang pagsusumite.',
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
              'Buksan ang Mga Bukid Ko at piliing gumawa ng bukid. Ilagay ang pangalan at lokasyon o kabuuang lawak kung alam. Suriin ang form at kumpirmahin ang paggawa.',
            result:
              'Lilitaw ang bukid sa Mga Bukid Ko. Kailangan ang pangalan; opsyonal ang address, lungsod at iba pang lokasyon.',
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
              'Piliin ang Pamahalaan ang mga plot para sa bukid. Hanapin ang tamang lugar gamit ang search at zoom. Piliin ang polygon tool, ilagay ang mga punto ng hangganan at tapusin ang hugis.',
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
              'Lilitaw sa Mga Kontrata Ko ang kontrata. Hindi muling mailalathala ang na-publish o tinanggihang rekomendasyon; hindi awtomatikong listing ang pagsusuri.',
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
              'Buksan ang Pagkakatugma, piliin ang dalawang pananim at gamitin ang paghahambing. Basahin ang paliwanag bago pumili ng rotation o intercropping.',
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
              'Buksan ang Mag-post ng Ani mula sa Mga Kontrata Ko. Piliin ang pananim, shelf life at pamagat; ilagay ang paglalarawan, dami sa kg at presyo kada kg. Markahang available ngayon o ilagay ang inaasahang petsa ng ani.',
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
              'Gamitin ang Gabay sa Presyo kung kailangan at tingnan ang source at petsa. Suriin ang pananim, dami, presyo, availability at pinagmulan. Isumite at kumpirmahin lamang kapag tama ang detalye.',
            result:
              'Ibabalik ka sa Mga Kontrata Ko kapag matagumpay. Kung mabigo, basahin ang error at ayusin o subukang muli; hindi garantiya ng benta ang reference price.',
          },
        },
        notes: {},
        recovery: {},
      },
      'manage-listings': {
        title: 'Subaybayan ang benta at resibo',
        intro: 'Magkasama sa Mga Kontrata Ko ang listing at contract records.',
        steps: {
          follow: {
            instruction:
              'Buksan ang Mga Kontrata Ko at suriin ang status at mga control ng listing. Sa karapat-dapat na kalakalan, mag-message para mag-coordinate. Sa cash purchase, buksan ang Mga Pag-apruba sa Cash at itala lang kapag natanggap na ang bayad.',
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
              'Buksan ang Farmer Marka ng Tiwala at tingnan ang breakdown, history at improvement tips. Isaalang-alang ang plot, rekomendasyon, kontrata, offer, transaction at tagal sa platform.',
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
              'Buksan ang Seguro sa Pananim at ilagay ang tamang impormasyon at status ng RSBSA. Suriin at kumpirmahin; kontakin ang agriculture office para sa opisyal na registration o pagwawasto.',
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
  'buying-harvests': {
    title: 'Pagbili ng ani',
    summary:
      'Hanapin ang available na ani o future contract, suriin ang dami at bumili nang may sapat na impormasyon.',
    prerequisites: {
      prepare:
        'Maaaring tumingin ang guest. Gumamit ng Mamimili account na beripikado ang email para sa checkout.',
    },
    sections: {
      'browse-and-filter': {
        title: 'Hanapin at salain ang pananim',
        intro: 'Pampubliko ang marketplace para sa paghahanap.',
        steps: {
          follow: {
            instruction:
              'Buksan ang Tumingin sa Palengke at maghanap ng pananim o pamagat. Ayusin ang minimum at maximum na presyo; nag-a-update habang nagta-type. Tingnan ang ibang pahina para sa mga resulta.',
            result:
              'Makikita ang mga tumutugmang listing at kontrata. Maaaring dahil sa filter ang walang resulta; alisin o paluwagin muna ito.',
          },
        },
        notes: {},
        recovery: {},
      },
      'inspect-listing': {
        title: 'Suriin ang listing',
        intro: 'Magkaiba ang timing ng available at hinaharap na ani.',
        steps: {
          follow: {
            instruction:
              'Piliin ang Tingnan ang mga Detalye. Basahin ang pananim, dami, presyo kada kg, availability o petsa, magsasaka at source badge. Suriin kung tugma ang panahon ng ani sa pangangailangan.',
            result:
              'Malalaman kung available na o future contract. Hindi garantiya ng delivery o kalidad ang provenance badge.',
          },
        },
        notes: {},
        recovery: {},
      },
      'review-checkout': {
        title: 'Suriin ang checkout',
        intro: 'Kinakalkula muli ang halaga ayon sa napiling dami.',
        steps: {
          follow: {
            instruction:
              'Mag-sign in bilang beripikadong Mamimili. Ilagay ang dami sa ipinakitang limitasyon, piliin ang payment option at suriin ang total at downpayment. Magpatuloy at kumpirmahin kung tama ang termino.',
            result:
              'Maaaring buksan ang PayMongo sa online checkout; may farmer confirmation sa cash. Hindi ibig sabihin ng pag-click na natanggap na ang cash.',
          },
        },
        notes: {},
        recovery: {},
      },
      'track-purchase': {
        title: 'Subaybayan ang purchase',
        intro: 'Ipinapakita ng Mga Binili Ko ang naitalang status.',
        steps: {
          follow: {
            instruction:
              'Buksan ang Mga Binili Ko at tingnan ang payment status at halagang bayad. Gamitin ang karapat-dapat na messaging para mag-coordinate. Gamitin lamang ang cancellation action na magagamit sa record.',
            result:
              'Makikita ang pag-usad ng bayad. Sa ka-trade pinag-uusapan ang aktuwal na delivery; walang ipinapangakong delivery service ang app.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'payments-and-purchases': {
    title: 'Bayad at mga purchase',
    summary:
      'Unawain ang halaga sa checkout, online payment, cash confirmation at purchase status.',
    prerequisites: {
      prepare:
        'Suriin ang listing at halagang ipinakita sa checkout. Magkaiba ang bayad at delivery.',
    },
    sections: {
      'payment-options': {
        title: 'Pumili ng paraan ng bayad',
        intro: 'May online PayMongo at cash sa mga suportadong checkout.',
        steps: {
          follow: {
            instruction:
              'Suriin ang mga option. Sa online payment, kumpirmahin bago sundin ang hosted checkout; sa Cash (Off-Site), sundin ang tagubilin at i-coordinate ang aktuwal na bayad sa magsasaka.',
            result:
              'Nakabatay sa option ang susunod na hakbang. Huwag ilagay ang payment credentials sa post, chat o support message.',
          },
        },
        notes: {},
        recovery: {},
      },
      'deposits-and-totals': {
        title: 'Unawain ang deposit at total',
        intro: 'Batay sa dami at availability ng ani ang halaga.',
        steps: {
          follow: {
            instruction:
              'Suriin ang dami na minultiply sa presyo kada kg at halagang kailangan ngayon. Kasalukuyang 10% downpayment ang future harvest; buong bayad ang available na ani. Suriin nang hiwalay ang natitirang halaga.',
            result:
              'Hindi full settlement ang downpayment. Ang nasa checkout ang sanggunian para sa order; suriin muli kapag nagbago ang dami.',
          },
        },
        notes: {},
        recovery: {},
      },
      'cash-confirmation': {
        title: 'Kumpirmahin ang natanggap na cash',
        intro: 'Pending record ang pagpili ng cash, hindi natanggap na pera.',
        steps: {
          follow: {
            instruction:
              'Makipag-ayos ang mamimili sa magsasaka. Buksan ng magsasaka ang Mga Pag-apruba sa Cash, suriin ang purchase at itala lamang ang natanggap na halaga bilang partial o full receipt, saka kumpirmahin.',
            result:
              'Nagbabago ang tala matapos kumpirmahin ang receipt. May natitira sa partial receipt; huwag markahang full payment bago matanggap.',
          },
        },
        notes: {},
        recovery: {},
      },
      'statuses-and-cancellation': {
        title: 'Tingnan ang status bago muling subukan',
        intro: 'Hindi patunay ng natapos na bayad ang return page lamang.',
        steps: {
          follow: {
            instruction:
              'Basahin ang Mga Binili Ko o payment record ng offer. Kung pending, tingnan muna bago gumawa ng bagong request. Kung failed, basahin ang error at gamitin ang retry. Kung may cancellation, suriin at kumpirmahin ito.',
            result:
              'Hiwalay ang pending, failed at completed payment. Hindi patunay ng delivery ang completed payment, at hindi garantiya ng automatic refund ang cancellation.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'demands-and-offers': {
    title: 'Buyer demand at farmer offer',
    summary:
      'Humiling ng pananim o tumugon sa mamimili, saka sundin ang allocation, bayad, delivery at completion.',
    prerequisites: {
      prepare:
        'Beripikahin ang email at mag-save ng marketplace address bago mag-post ng demand o magsumite ng offer.',
    },
    sections: {
      'post-demand': {
        title: 'Mamimili: mag-post ng demand',
        intro: 'Sinasabi ng demand ang kailangan at kung kailan.',
        steps: {
          follow: {
            instruction:
              'Mag-save ng address sa profile at buksan ang Mag-post ng Demand. Ilagay ang pamagat, pananim, dami, target price, delivery address, needed-by date, expiry at paglalarawan. Suriin ang petsa at kumpirmahin.',
            result:
              'Lilitaw sa Mga Demand Ko at maaaring tumugon ang magsasaka. Hiling ang target price, hindi garantiya ng supply.',
          },
        },
        notes: {},
        recovery: {},
      },
      'submit-offer': {
        title: 'Magsasaka: magsumite ng offer',
        intro: 'Nakaugnay ang offer sa bukas na demand.',
        steps: {
          follow: {
            instruction:
              'Mag-save ng address at buksan ang Mga Demand ng Pananim. Suriin ang pananim, petsa at natitirang dami. Ilagay ang daming hindi lalampas sa kailangan, presyo kada kg at opsyonal na mensahe, saka kumpirmahin.',
            result:
              'Subaybayan sa Mga Alok Ko. Isa lang ang aktibong offer mo bawat demand; gamitin ang withdrawal kung magagamit bago palitan.',
          },
        },
        notes: {},
        recovery: {},
      },
      'decide-offers': {
        title: 'Mamimili: suriin ang offer',
        intro: 'Maaaring maraming magsasaka ang magsupply sa demand.',
        steps: {
          follow: {
            instruction:
              'Buksan ang Mga Demand Ko, suriin ang offer at ihambing ang dami, presyo at termino. Suriin ang kumpirmasyon ng pagtanggap o pagtanggi. Tingnan ang natitirang dami para sa kailangan pa.',
            result:
              'Nakalaan ang dami kapag tinanggap at maaaring mag-chat, ngunit hindi pa bayad. Magkaiba ang fully allocated at fulfilled.',
          },
        },
        notes: {},
        recovery: {},
      },
      'pay-and-deliver': {
        title: 'Bayaran at itala ang delivery',
        intro: 'Magkahiwalay ang acceptance, bayad at delivery.',
        steps: {
          follow: {
            instruction:
              'Buksan ng mamimili ang checkout ng tinanggap na offer at kumpirmahin ang termino. Sa cash, itala ng magsasaka ang aktuwal na receipt. Kapag paid o partially paid at na-deliver na, suriin at kumpirmahin ang delivered action.',
            result:
              'Makikita ang naitalang pag-usad. I-coordinate ang delivery; huwag markahang delivered dahil lamang may bayad na.',
          },
        },
        notes: {},
        recovery: {},
      },
      'complete-and-settle': {
        title: 'Kumpirmahin ang completion at balance',
        intro: 'Kinukumpleto ng mamimili matapos ang naitalang delivery.',
        steps: {
          follow: {
            instruction:
              'Pagkatanggap ng ani, suriin at kumpirmahin ng mamimili ang completion. Kung may natitirang online-downpayment balance sa delivered o completed offer, gamitin ng magsasaka ang settlement confirmation kapag natanggap na ang buong bayad.',
            result:
              'Magkaiba ang completion at halagang bayad. Itinatala ng settlement ang full receipt; hindi ito panibagong automatic PayMongo checkout.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'price-guide': {
    title: 'Paggamit ng Gabay sa Presyo',
    summary:
      'Basahin ang price reference kasama ang source, petsa at limitasyon bago pumili ng termino.',
    prerequisites: {
      prepare:
        'Nasa mga kaukulang listing, contract, demand at offer form ang Gabay sa Presyo; wala itong hiwalay na dashboard page.',
    },
    sections: {
      'open-price-guide': {
        title: 'Buksan ang naka-embed na gabay',
        intro: 'Nasa tabi ng mga suportadong price field ang gabay.',
        steps: {
          follow: {
            instruction:
              'Buksan ang Gabay sa Presyo sa listing, contract publishing, demand o offer form. Tingnan ang pananim, lokasyon at available na reference price.',
            result:
              'May konteksto sa pagpili ng presyo. Hindi ito obligadong selling price, at hindi nangangahulugang walang halaga ang pananim kung walang gabay.',
          },
        },
        notes: {},
        recovery: {},
      },
      'sources-and-freshness': {
        title: 'Basahin ang source at petsa',
        intro: 'Magkakaiba ang batayan ng mga price source.',
        steps: {
          follow: {
            instruction:
              'Basahin ang source, as-of date at stale indicator. Sinusuri muna ang YieldGrid trade data, saka DA reference, at AI estimate kung walang naunang source. Ihambing ang konteksto sa halip na ituring na parehong lokal na presyo.',
            result:
              'May AI label ang AI estimate at hindi DA quotation. Walang garantiya ng pinakabagong farm-gate price o halagang ibabayad ng mamimili.',
          },
        },
        notes: {},
        recovery: {},
      },
      'unavailable-prices': {
        title: 'Kapag walang magamit na presyo',
        intro: 'Nag-iiba ang coverage at pagiging bago ng reference.',
        steps: {
          follow: {
            instruction:
              'Basahin ang unavailable o error state at gamitin ang retry kung mayroon. Suriin ang pananim at lokasyon at gumamit ng maaasahang lokal na impormasyon. Suriin ang inilagay na presyo bago kumpirmahin.',
            result:
              'Ikaw ang pumipili ng termino. Hindi pinapalitan ng gabay ang negosasyon, lokal na market check o pag-unawa sa gastos.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
  'community-and-messaging': {
    title: 'Komunidad at pagmemensahe',
    summary:
      'Talakayin ang praktikal na tanong sa komunidad at mag-coordinate ng karapat-dapat na kalakalan sa mensahe.',
    prerequisites: {
      prepare:
        'Beripikahin ang email para sa community at messaging actions. Kailangan ng karapat-dapat na transaction sa kausap para sa chat.',
    },
    sections: {
      'chat-eligibility': {
        title: 'Alamin kung kailan may chat',
        intro: 'Ikinokonekta ng chat ang mga kasali sa karapat-dapat na kalakalan.',
        steps: {
          follow: {
            instruction:
              'Buksan ang messaging mula sa kaukulang purchase o tinanggap na demand offer. Kailangan ang access ng parehong miyembro; hindi sapat ang failed payment o hindi tinanggap na offer.',
            result:
              'Maaaring may chat bago bayaran ang tinanggap na offer. Hindi sapat ang pagkakakilala sa profile para sa malayang messaging.',
          },
        },
        notes: {},
        recovery: {},
      },
      'use-messages': {
        title: 'Mag-coordinate sa mensahe',
        intro: 'Panatilihing malinaw at magalang ang pag-uusap.',
        steps: {
          follow: {
            instruction:
              'Buksan ang conversation, basahin ang history at gumawa ng mensahe sa limitasyon. Suriin at kumpirmahin ang pagpapadala; mag-attach lamang ng suportadong file sa tamang laki. Gamitin ang retry kung hindi ma-load ang history.',
            result:
              'Lilitaw ang mensahe at attachment. Huwag ibahagi ang password, verification link o payment credentials.',
          },
        },
        notes: {},
        recovery: {},
      },
      'forum-participation': {
        title: 'Sumali sa talakayan',
        intro: 'Para sa pagbabahagi ng praktikal na kaalaman ang forum.',
        steps: {
          follow: {
            instruction:
              'Tingnan ang kategorya at thread, hanapin ang paksa at basahin ang mga reply. Sa pag-ambag, gamitin ang form, sundin ang text at file limit at suriin ang kumpirmasyon. Gamitin ang vote o accepted answer kung pinahihintulutan.',
            result:
              'Kasama sa usapan ang ambag mo. Suriin ang karanasan at AI information kasama ng lokal na eksperto; hindi ito garantisadong payo.',
          },
        },
        notes: {},
        recovery: {},
      },
      'report-content': {
        title: 'Mag-report ng alalahanin',
        intro: 'Gamitin ang reporting control sa halip na palalain ang pampublikong pagtatalo.',
        steps: {
          follow: {
            instruction:
              'Buksan ang report action, pumili ng dahilan at ilarawan sa limitasyon. Suriin at kumpirmahin. Sa teknikal na problema ng app, gamitin ang Mag-ulat ng Isyu o Kontak.',
            result:
              'Matatanggap ng team ang report para sa pagsusuri. Hindi agad desisyon sa content o account ang naisumiteng report.',
          },
        },
        notes: {},
        recovery: {},
      },
    },
  },
}
