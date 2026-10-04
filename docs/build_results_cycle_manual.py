from pathlib import Path

# Share the established document style without executing the sponsor content builder.
base=Path(__file__).parent
exec((base/'build_sponsor_manual.py').read_text(encoding='utf-8').split('# Cover')[0])
OUT=base/'ElimuTaifa_Results_Cycle_Management_Guide.docx'
d.core_properties.title='ElimuTaifa Results Cycle Management Guide'
d.core_properties.subject='Kusimamia miaka ya matokeo na mabadiliko ya chanzo'
s.header.paragraphs[0].text='ELIMUTAIFA  |  Results Cycle Management'

p('ELIMUTAIFA').runs[0].bold=True
d.add_paragraph('Results Cycle Management Guide','Title')
d.add_paragraph('Mwongozo wa kusimamia miaka na vyanzo vya matokeo','Subtitle')
p('Toleo 1.0  |  4 Oktoba 2026')
h('Kwa owners na wasimamizi wa matokeo')
p('Cycle ni mchanganyiko wa aina ya mtihani na mwaka wake, mfano CSEE 2026. Kila cycle ina chanzo, njia za kurasa, hali ya publication na taarifa za ukaguzi. Mwongozo huu unaeleza kuongeza mwaka, kukagua NECTA au Maktaba / TETEA, kuchapisha na kurekebisha chanzo kilichohama.')
p('Utaratibu wa kawaida ni Open cycle → Check source → Publish cycle. Mfumo huchagua sample school wenyewe. Advanced settings zinahitajika tu ikiwa njia za chanzo hazijapatikana au zimekuwa tofauti.')
h('Mipaka ya mwongozo')
p('Mwongozo huu unahusu ACSEE, CSEE, FTNA, PSLE na SFNA. Form One na Form Five / Colleges zina cycle management tofauti na hazijaelezwa hapa.')
p('Hakuna live checks zilizofanywa kwa ajili ya document hii. Hakuna matokeo ya ukaguzi, sample school au uthibitisho wa upatikanaji wa mwaka uliobuniwa. Mifano ya paths ni ya kueleza muundo; si URLs zilizothibitishwa kuwa zinafanya kazi.')

page('Yaliyomo')
table(['Sehemu','Mada'],[('1','Kufungua na kuelewa cycle'),('2','Kukagua na kuchapisha mwaka'),('3','Year rollover na source relocation'),('4','Advanced settings'),('5','Kushughulikia errors'),('6','Pause archive na recovery'),('7','Monitoring na checklist'),('8','Daftari la ukaguzi')],[.8,6.1])
h('Mwongozo wa haraka')
steps(['Fungua Admin → Result Pages → Open examination monitoring.','Chagua Examination na Year, kisha Open cycle.','Chagua NECTA au Maktaba / TETEA na bofya Check source.','Kagua health na ujumbe wa ukaguzi.','Ikiwa Ready to publish, bofya Publish cycle.','Kagua public search na school browsing kwa mwaka huo.'])
p('Admin anaweza kusoma monitoring. Akaunti yenye role ya owner inaweza kufanya checks na kubadilisha cycles. Hii ni ruhusa ya owner; haiwekewi main owner pekee kama network ads setup.')

page('1 Kufungua na kuelewa cycle')
steps(['Ingia admin na fungua Result Pages.','Bofya Open examination monitoring.','Chagua Examination: ACSEE, CSEE, FTNA, PSLE au SFNA.','Weka Year, kisha bofya Open cycle.'])
p('Year inakubali 2010 hadi mwaka wa sasa wa server. Kuchagua mwaka hakumaanishi matokeo yake yametolewa. Cycle mpya huanza kama configuration ya maandalizi mpaka ukaguzi na publication zikamilike.')
h('Health na public status ni tofauti')
table(['Health','Maana'],[('Healthy','Published mapping yenye sample check ya karibuni iliyofaulu'),('Ready to publish','Sample check imefaulu; cycle inasubiri publication'),('Not checked','Hakuna verification ya kutosha au mapping ni inherited'),('Check due','Ukaguzi wa manual umepita saa 24'),('Needs attention','Ukaguzi umeshindwa au kuna source errors zinazorudiwa'),('Paused','Cycle ina hali ya suspended au archived')],[1.8,5.1])
table(['Public status','Tabia'],[('Draft','Haionekani kwa public'),('Published','Inapatikana kwenye public search na school browsing'),('Suspended','Imefichwa kwa Pause cycle'),('Archived','Imeondolewa kwenye public search kwa Archive cycle')],[1.8,5.1])
p('Health ya Needs attention inaweza kuonekana hata cycle ikiwa paused. Soma public status na ujumbe wa ukaguzi pamoja. Published au inherited si ushahidi kwamba kila shule imekaguliwa.')

page('2 Kukagua na kuchapisha mwaka')
h('Check source')
steps(['Fungua cycle ya mtihani na mwaka sahihi.','Chagua Source provider → NECTA au Maktaba / TETEA.','Bofya Check source na usubiri ikamilike.','Soma ujumbe unaoonyesha directory na sample school.'])
p('Mfumo hutafuta directory na sample school moja kwa moja. Kwa PSLE na SFNA, hutafuta pia district directory. Kwa matumizi ya kawaida huhitaji kuandika school code, district code au {school}.')
p('Check hutumia live pages na hupita cache ya matokeo. Ina kikomo cha hadi upstream requests nane. Sample check haihakiki kila shule wala kila candidate. HTTP 200 pekee haitoshi kuthibitisha kwamba result layout inasomeka.')
h('Publish cycle')
steps(['Hakikisha hali inaonyesha Ready to publish.','Bofya Publish cycle.','Kagua public result search kwa mwaka huo.','Kagua school browsing na shule inayojulikana kutoka kwenye source hiyo.'])
p('Publication inahitaji manual Check source iliyofaulu ndani ya saa 24 zilizopita. Ikiwa muda umepita, kagua tena. Check ya background si mbadala wa hatua hii ya manual publication.')
p('Check iliyofaulu kwa mapping ileile ya cycle iliyo Published huiweka Published. Ikiwa check imegundua mapping tofauti na kuihifadhi, cycle inarudi Draft mpaka Publish cycle ifanywe.')
h('Check ikishindwa')
p('Mfumo huhifadhi mapping na public status ya awali, pamoja na taarifa ya failure. Haufanyi automatic pause ya cycle iliyo Published. Public search inaendelea kujaribu chanzo kilichohifadhiwa; hii haihakikishi kuwa source bado inafanya kazi.')

page('3 Year rollover na source relocation')
h('Mwaka mpya')
p('Hakuna cap ya kudumu ya 2026. Year limit inatumia mwaka wa sasa wa server. Server ikifika 2027, form itaruhusu 2027. Saa na tarehe ya server lazima ziwe sahihi.')
p('Kuruhusiwa kwa mwaka mpya hakuuundi cycle iliyochapishwa wala kuthibitisha matokeo yake. Owner bado lazima afungue mwaka, afanye Check source na Publish cycle baada ya source kupatikana.')
steps(['Baada ya mwaka kubadilika, fungua Examination na mwaka mpya.','Chagua provider inayochapisha matokeo ya mwaka huo.','Fanya Check source.','Kama source haijatolewa, usichapishe; kagua tena inapopatikana.','Baada ya check kufaulu, Publish cycle na ukague public routes.'])
h('Matokeo ya zamani yakihamishwa')
p('Usibadilishe provider kwa kuhesabu umri wa mwaka pekee. Mfumo hauhamishi NECTA kwenda Maktaba / TETEA moja kwa moja. Mabadiliko yawe baada ya kukagua source halisi.')
steps(['Tambua cycle iliyoathirika, mfano mwaka wa zamani wa CSEE.','Fungua mwaka huo na uchague Maktaba / TETEA kama source mpya ipo huko.','Bofya Check source.','Ikifaulu na mapping ikibadilika, cycle itakuwa Draft.','Bofya Publish cycle na ukague candidate search pamoja na school browsing.'])
p('Mabadiliko ya cycle moja hayaamui provider ya miaka mingine. Candidate search na school browsing hutumia published mapping ileile, hivyo kagua zote baada ya source change.')
p('Ikiwa source mpya inashindwa, mapping ya awali hubaki. Tumia Pause cycle ikiwa mwaka haupaswi kuendelea kuonekana wakati unarekebisha.')

page('4 Advanced settings')
p('Fungua Advanced settings & history kwa source paths zisizo za kawaida. Settings hizi zinaweza kubadilisha route ya public results. Nakili taarifa kutoka kwenye chanzo halisi; usikisie filenames.')
table(['Field','Maana na kanuni'],[('Source base URL','HTTPS URL ya examination/year directory iliyoidhinishwa; lazima iishie /'),('School path','Relative .htm/.html path yenye {school} mara moja tu'),('Directory path','Secondary: fixed filename. PSLE/SFNA: {district} mara moja'),('Filename case','Lowercase au Uppercase kulingana na filename ya source'),('School code format','Standard, au Legacy PSLE kwa PSLE inayotumia muundo huo')],[1.9,5.0])
p('Hosts zinazoruhusiwa na implementation ya sasa ni onlinesys.necta.go.tz, matokeo.necta.go.tz na maktaba.tetea.org. Host au directory mpya kabisa inaweza kuhitaji developer update; usijaribu kupita validation.')
h('Maana ya placeholders')
bullets(['{school} hubadilishwa kuwa school code wakati wa kutafuta matokeo.','{district} hubadilishwa kuwa district code kwenye primary directory.','School path haiwezi kuwa na {district}.','Secondary directory path haina placeholders.'])
h('Mifano ya muundo pekee')
table(['Matumizi','Mfano wa relative path'],[('School','results/{school}.htm'),('PSLE school','results/shl_{school}.htm'),('Secondary directory','index.htm'),('Primary district directory','results/distr_{district}.htm')],[2.5,4.4])
p('Mifano hii haijathibitishwa kwa cycle yoyote. Source inaweza kutumia prefix, case au filename tofauti.')
steps(['Jaza fields sahihi na bofya Save advanced settings.','Mabadiliko ya mapping huondoa verification na kurudisha Draft.','Fanya Check source tena, kisha Publish cycle baada ya kufaulu.'])

page('5 Kushughulikia errors')
table(['Ujumbe au tatizo','Maana na hatua'],[
('The school path must contain exactly one {school} placeholder.','Weka {school} mara moja tu kwenye School path. Usiweke school code halisi badala yake.'),
('The sample school was not found in this directory.','Directory na sample route hazilingani. Kagua provider, year na paths; fanya Check source tena.'),
('No readable candidate rows were found for the sample school. Review the result layout.','Page haijatoa candidate rows zinazosomeka na parser. Hakiki ni result page sahihi. Layout mpya inaweza kuhitaji developer update.'),
('No result directory passed the check.','Matokeo huenda hayajatolewa, source imehama au layout imebadilika. Hakiki source halisi.'),
('The source could not be reached.','Kagua internet, HTTPS certificate na provider availability, kisha jaribu tena.'),
('Too many requests.','Subiri angalau dakika moja kabla ya check nyingine.'),
('The check reached its page limit.','Automatic discovery imefikia budget. Kagua link na Advanced settings.'),
('Click Check source first.','Fanya manual check iliyofaulu ndani ya saa 24 kabla ya publication.'),
('This cycle has changed. Reload the page before continuing.','Mabadiliko mengine yamehifadhiwa. Reload na uhakiki current settings kabla ya kujaribu tena.')],[2.95,3.95])
p('Kwa SFNA, readable candidate rows failure haitoshi kuhitimisha kuwa hakuna matokeo. Inaweza kuwa wrong page au layout ambayo parser haiungi mkono. Usilazimishe publication; wasilisha cycle na source URL kwa developer bila candidate details zisizohitajika.')

page('6 Pause archive na recovery')
h('Pause cycle')
steps(['Fungua cycle iliyohifadhiwa.','Bofya Pause cycle na uthibitishe ujumbe wa action.','Hakikisha public status ni Suspended.'])
p('Pause huficha mwaka kutoka public search. Hutumika wakati source inahitaji marekebisho. Kurejesha mwaka kunahitaji Check source na Publish cycle.')
h('Archive cycle')
p('Archive cycle ipo kwenye Advanced settings & history kwa owner. Inaficha cycle kwenye public search kwa status Archived; si kufuta rekodi yake.')
h('Restore as Draft')
steps(['Fungua Advanced settings & history.','Kagua Previous configurations na provider ya kila snapshot.','Chagua Restore as Draft kwa configuration unayotaka.','Fanya Check source mpya.','Bofya Publish cycle baada ya check kufaulu.','Kagua public routes tena.'])
p('History huonyesha configurations kumi za karibuni. Restore hairudishi published approval moja kwa moja. Configuration iliyorejeshwa inaweza kuwa na source ya zamani ambayo haipatikani sasa.')
h('Athari ya mabadiliko')
p('Cycle changes na checks huandikwa kwenye audit history. Stale concurrent edits hukataliwa ili mabadiliko ya owner mmoja yasifute kimya kimya ya mwingine. Reload page ikisema cycle imebadilika.')
p('Mapping ikibadilika, cache namespace hubadilika kwa cycle hiyo. Huhitaji kufuta caches za miaka mingine kwa source change ya mwaka mmoja.')

page('7 Monitoring na checklist')
h('Manual checks na background monitoring')
p('Check source ya admin ndiyo hatua ya review na publication. Source checks za background notifications ni za health alerts; hazibadilishi published mapping wala kuchapisha mwaka mpya.')
p('Background checks hutegemea worker na checks preference. Implementation huchagua published cycle moja kwa rotation, kwa nafasi ya angalau dakika tano kati ya ticks, na hujaribu kila cycle baada ya angalau saa 24 tangu check yake ya background. Hii si ahadi ya ukaguzi wa kila mwaka kwa muda maalumu.')
p('Failures tatu mfululizo zinaweza kuanzisha critical notification ikiwa critical alerts zimewashwa. Email pia hutegemea worker na mail connection kufanya kazi. Tumia Traffic & Errors kuchunguza source failures; usitegemee email pekee.')
p('Kwa setup ya local computer, automatic worker haitafanya kazi kompyuta ikiwa imezimwa au imelala. Hosting inahitaji worker schedule ya server.')
h('Checklist ya publication au source change')
check(['Examination na Year ni sahihi.','Provider ni chanzo cha mwaka huo, si makadirio ya umri wa mwaka.','Manual Check source imefaulu ndani ya saa 24.','Directory na sample school zimesomeka.','Status imewekwa Published kupitia Publish cycle.','Candidate search imekaguliwa kwa sample inayojulikana.','School browsing imekaguliwa kwa mwaka huo.','Traffic & Errors imekaguliwa ikiwa kuna tatizo.','Source change au issue imeandikwa kwenye daftari la ukaguzi.'])
p('Ratiba inayopendekezwa: kagua kabla na baada ya release, baada ya taarifa ya relocation, unapopokea alert na kabla ya publication. Sample check haithibitishi correctness ya kila shule au traffic capacity ya mfumo.')

page('8 Daftari la ukaguzi')
p('Tumia rekodi hii kwa kila cycle iliyokaguliwa au iliyobadilishwa. Daftari ni sehemu ya document; si feature mpya ndani ya mfumo. Nakili ukurasa huu kwa rekodi nyingine.')
table(['Taarifa','Nafasi ya kujaza'],[(x,'') for x in ['Examination na Year','Tarehe na muda kwa EAT','Owner aliyekagua','Provider ya awali','Provider iliyokaguliwa','Source URL','Ujumbe halisi wa check','Public status baada ya hatua','Public search imekaguliwa','School browsing imekaguliwa','Error au developer follow up','Hatua inayofuata']],[2.8,4.1])
p('Andika results za check halisi pekee. Usijaze Passed, Healthy au Published ikiwa hatua husika haijathibitishwa.')

d.save(OUT)
print(OUT)
