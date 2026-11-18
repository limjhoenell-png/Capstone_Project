using System.Collections.Generic;

namespace FingerprintAttendance.Core
{
    public static partial class PhilippineGeoData
    {
        public static List<GeoItem> GetBarangays(string cityCode)
        {
            switch (cityCode)
            {
                case "118201000": // Compostela
                    return new List<GeoItem>
                    {
                        new GeoItem("118201016", "Aurora"),
                        new GeoItem("118201001", "Bagongon"),
                        new GeoItem("118201002", "Gabi"),
                        new GeoItem("118201003", "Lagab"),
                        new GeoItem("118201004", "Mangayon"),
                        new GeoItem("118201005", "Mapaca"),
                        new GeoItem("118201006", "Maparat"),
                        new GeoItem("118201007", "New Alegria"),
                        new GeoItem("118201008", "Ngan"),
                        new GeoItem("118201009", "Osmeña"),
                        new GeoItem("118201010", "Panansalan"),
                        new GeoItem("118201011", "Poblacion"),
                        new GeoItem("118201012", "San Jose"),
                        new GeoItem("118201013", "San Miguel"),
                        new GeoItem("118201014", "Siocon"),
                        new GeoItem("118201015", "Tamia")
                    };
                case "118202000": // Laak
                    return new List<GeoItem>
                    {
                        new GeoItem("118202001", "Aguinaldo"),
                        new GeoItem("118202016", "Amor Cruz"),
                        new GeoItem("118202017", "Ampawid"),
                        new GeoItem("118202018", "Andap"),
                        new GeoItem("118202019", "Anitap"),
                        new GeoItem("118202020", "Bagong Silang"),
                        new GeoItem("118202002", "Banbanon"),
                        new GeoItem("118202021", "Belmonte"),
                        new GeoItem("118202003", "Binasbas"),
                        new GeoItem("118202022", "Bullucan"),
                        new GeoItem("118202004", "Cebulida"),
                        new GeoItem("118202023", "Concepcion"),
                        new GeoItem("118202024", "Datu Ampunan"),
                        new GeoItem("118202025", "Datu Davao"),
                        new GeoItem("118202026", "Doña Josefa"),
                        new GeoItem("118202027", "El Katipunan"),
                        new GeoItem("118202005", "Il Papa"),
                        new GeoItem("118202028", "Imelda"),
                        new GeoItem("118202029", "Inacayan"),
                        new GeoItem("118202006", "Kaligutan"),
                        new GeoItem("118202007", "Kapatagan"),
                        new GeoItem("118202008", "Kidawa"),
                        new GeoItem("118202009", "Kilagding"),
                        new GeoItem("118202010", "Kiokmay"),
                        new GeoItem("118202014", "Laac (Poblacion)"),
                        new GeoItem("118202011", "Langtud"),
                        new GeoItem("118202012", "Longanapan"),
                        new GeoItem("118202030", "Mabuhay"),
                        new GeoItem("118202031", "Macopa"),
                        new GeoItem("118202032", "Malinao"),
                        new GeoItem("118202033", "Mangloy"),
                        new GeoItem("118202034", "Melale"),
                        new GeoItem("118202013", "Naga"),
                        new GeoItem("118202035", "New Bethlehem"),
                        new GeoItem("118202036", "Panamoren"),
                        new GeoItem("118202037", "Sabud"),
                        new GeoItem("118202015", "San Antonio"),
                        new GeoItem("118202038", "Santa Emilia"),
                        new GeoItem("118202039", "Santo Niño"),
                        new GeoItem("118202040", "Sisimon")
                    };
                case "118203000": // Mabini
                    return new List<GeoItem>
                    {
                        new GeoItem("118203012", "Anitapan"),
                        new GeoItem("118203013", "Cabuyuan"),
                        new GeoItem("118203002", "Cadunan"),
                        new GeoItem("118203007", "Cuambog (Poblacion)"),
                        new GeoItem("118203014", "Del Pilar"),
                        new GeoItem("118203016", "Golden Valley (Maraut)"),
                        new GeoItem("118203015", "Libodon"),
                        new GeoItem("118203017", "Pangibiran"),
                        new GeoItem("118203006", "Pindasan"),
                        new GeoItem("118203018", "San Antonio"),
                        new GeoItem("118203011", "Tagnanan (Mampising)")
                    };
                case "118204000": // Maco
                    return new List<GeoItem>
                    {
                        new GeoItem("118204001", "Anibongan"),
                        new GeoItem("118204002", "Anislagan"),
                        new GeoItem("118204003", "Binuangan"),
                        new GeoItem("118204004", "Bucana"),
                        new GeoItem("118204005", "Calabcab"),
                        new GeoItem("118204006", "Concepcion"),
                        new GeoItem("118204007", "Dumlan"),
                        new GeoItem("118204008", "Elizalde (Somil)"),
                        new GeoItem("118204010", "Gubatan"),
                        new GeoItem("118204011", "Hijo"),
                        new GeoItem("118204012", "Kinuban"),
                        new GeoItem("118204013", "Langgam"),
                        new GeoItem("118204014", "Lapu-lapu"),
                        new GeoItem("118204015", "Libay-libay"),
                        new GeoItem("118204016", "Limbo"),
                        new GeoItem("118204017", "Lumatab"),
                        new GeoItem("118204018", "Magangit"),
                        new GeoItem("118204031", "Mainit"),
                        new GeoItem("118204019", "Malamodao"),
                        new GeoItem("118204020", "Manipongol"),
                        new GeoItem("118204021", "Mapaang"),
                        new GeoItem("118204022", "Masara"),
                        new GeoItem("118204023", "New Asturias"),
                        new GeoItem("118204032", "New Barili"),
                        new GeoItem("118204033", "New Leyte"),
                        new GeoItem("118204034", "New Visayas"),
                        new GeoItem("118204035", "Panangan"),
                        new GeoItem("118204024", "Panibasan"),
                        new GeoItem("118204025", "Panoraon"),
                        new GeoItem("118204009", "Pangi (Gaudencio Antonio)"),
                        new GeoItem("118204026", "Poblacion"),
                        new GeoItem("118204027", "San Juan"),
                        new GeoItem("118204028", "San Roque"),
                        new GeoItem("118204029", "Sangab"),
                        new GeoItem("118204030", "Taglawig"),
                        new GeoItem("118204036", "Tagbaros"),
                        new GeoItem("118204037", "Teresa")
                    };
                case "118205000": // Maragusan
                    return new List<GeoItem>
                    {
                        new GeoItem("118205001", "Bagong Silang"),
                        new GeoItem("118205006", "Bahi"),
                        new GeoItem("118205007", "Cambagang"),
                        new GeoItem("118205008", "Coronobe"),
                        new GeoItem("118205009", "Katipunan"),
                        new GeoItem("118205010", "Lahi"),
                        new GeoItem("118205011", "Langgawisan"),
                        new GeoItem("118205012", "Mabugnao"),
                        new GeoItem("118205013", "Magcagong"),
                        new GeoItem("118205014", "Mahayahay"),
                        new GeoItem("118205002", "Mapawa"),
                        new GeoItem("118205003", "Maragusan (Poblacion)"),
                        new GeoItem("118205015", "Mauswagon"),
                        new GeoItem("118205004", "New Albay"),
                        new GeoItem("118205016", "New Katipunan"),
                        new GeoItem("118205017", "New Manay"),
                        new GeoItem("118205018", "New Panay"),
                        new GeoItem("118205019", "Paloc"),
                        new GeoItem("118205020", "Pamintaran"),
                        new GeoItem("118205021", "Parasanon"),
                        new GeoItem("118205022", "Talian"),
                        new GeoItem("118205023", "Tandik"),
                        new GeoItem("118205024", "Tigbao"),
                        new GeoItem("118205005", "Tupas")
                    };
                case "118206000": // Mawab
                    return new List<GeoItem>
                    {
                        new GeoItem("118206001", "Andili"),
                        new GeoItem("118206002", "Bawani"),
                        new GeoItem("118206003", "Concepcion"),
                        new GeoItem("118206004", "Malinawon"),
                        new GeoItem("118206005", "Nueva Visayas"),
                        new GeoItem("118206006", "Nuevo Iloco"),
                        new GeoItem("118206007", "Poblacion"),
                        new GeoItem("118206008", "Salvacion"),
                        new GeoItem("118206009", "Saosao"),
                        new GeoItem("118206010", "Sawangan"),
                        new GeoItem("118206011", "Tuboran")
                    };
                case "118207000": // Monkayo
                    return new List<GeoItem>
                    {
                        new GeoItem("118207001", "Awao"),
                        new GeoItem("118207002", "Babag"),
                        new GeoItem("118207003", "Banlag"),
                        new GeoItem("118207004", "Baylo"),
                        new GeoItem("118207005", "Casoon"),
                        new GeoItem("118207007", "Haguimitan"),
                        new GeoItem("118207006", "Inambatan"),
                        new GeoItem("118207008", "Macopa"),
                        new GeoItem("118207009", "Mamunga"),
                        new GeoItem("118207022", "Mount Diwata (Diwalwal)"),
                        new GeoItem("118207010", "Naboc"),
                        new GeoItem("118207011", "Olaycon"),
                        new GeoItem("118207012", "Pasian (Santa Filomena)"),
                        new GeoItem("118207013", "Poblacion"),
                        new GeoItem("118207014", "Rizal"),
                        new GeoItem("118207015", "Salvacion"),
                        new GeoItem("118207016", "San Isidro"),
                        new GeoItem("118207017", "San Jose"),
                        new GeoItem("118207019", "Tubo-tubo (New Del Monte)"),
                        new GeoItem("118207021", "Union"),
                        new GeoItem("118207020", "Upper Ulip")
                    };
                case "118208000": // Montevista
                    return new List<GeoItem>
                    {
                        new GeoItem("118208001", "Banagbanag"),
                        new GeoItem("118208002", "Banglasan"),
                        new GeoItem("118208003", "Bankerohan Norte"),
                        new GeoItem("118208004", "Bankerohan Sur"),
                        new GeoItem("118208005", "Camansi"),
                        new GeoItem("118208006", "Camantangan"),
                        new GeoItem("118208009", "Canidkid"),
                        new GeoItem("118208007", "Concepcion"),
                        new GeoItem("118208008", "Dauman"),
                        new GeoItem("118208010", "Lebanon"),
                        new GeoItem("118208011", "Linoan"),
                        new GeoItem("118208012", "Mayaon"),
                        new GeoItem("118208014", "New Calape"),
                        new GeoItem("118208016", "New Cebulan (Sambayon)"),
                        new GeoItem("118208015", "New Dalaguete"),
                        new GeoItem("118208017", "New Visayas"),
                        new GeoItem("118208019", "Prosperidad"),
                        new GeoItem("118208020", "San Jose (Poblacion)"),
                        new GeoItem("118208021", "San Vicente"),
                        new GeoItem("118208022", "Tapia")
                    };
                case "118209000": // Nabunturan
                    return new List<GeoItem>
                    {
                        new GeoItem("118209001", "Anislagan"),
                        new GeoItem("118209002", "Antequera"),
                        new GeoItem("118209003", "Basak"),
                        new GeoItem("118209027", "Bayabas"),
                        new GeoItem("118209028", "Bukal"),
                        new GeoItem("118209004", "Cabacungan"),
                        new GeoItem("118209005", "Cabidianan"),
                        new GeoItem("118209006", "Katipunan"),
                        new GeoItem("118209007", "Libasan"),
                        new GeoItem("118209008", "Linda"),
                        new GeoItem("118209009", "Magading"),
                        new GeoItem("118209010", "Magsaysay"),
                        new GeoItem("118209011", "Mainit"),
                        new GeoItem("118209012", "Manat"),
                        new GeoItem("118209013", "Matilo"),
                        new GeoItem("118209014", "Mipangi"),
                        new GeoItem("118209015", "New Dauis"),
                        new GeoItem("118209016", "New Sibonga"),
                        new GeoItem("118209017", "Ogao"),
                        new GeoItem("118209018", "Pangutosan"),
                        new GeoItem("118209019", "Poblacion"),
                        new GeoItem("118209020", "San Isidro"),
                        new GeoItem("118209021", "San Roque"),
                        new GeoItem("118209022", "San Vicente"),
                        new GeoItem("118209023", "Santa Maria"),
                        new GeoItem("118209024", "Santo Niño (Kao)"),
                        new GeoItem("118209025", "Sasa"),
                        new GeoItem("118209026", "Tagnocon")
                    };
                case "118210000": // New Bataan
                    return new List<GeoItem>
                    {
                        new GeoItem("118210013", "Andap"),
                        new GeoItem("118210001", "Bantacan"),
                        new GeoItem("118210002", "Batinao"),
                        new GeoItem("118210011", "Cabinuangan (Poblacion)"),
                        new GeoItem("118210003", "Camanlangan"),
                        new GeoItem("118210004", "Cogonon"),
                        new GeoItem("118210005", "Fatima"),
                        new GeoItem("118210014", "Kahayag"),
                        new GeoItem("118210006", "Katipunan"),
                        new GeoItem("118210008", "Magangit"),
                        new GeoItem("118210007", "Magsaysay"),
                        new GeoItem("118210015", "Manurigao"),
                        new GeoItem("118210009", "Pagsabangan"),
                        new GeoItem("118210010", "Panag"),
                        new GeoItem("118210012", "San Roque"),
                        new GeoItem("118210016", "Tandawan")
                    };
                case "118211000": // Pantukan
                    return new List<GeoItem>
                    {
                        new GeoItem("118211013", "Araibo"),
                        new GeoItem("118211001", "Bongabong"),
                        new GeoItem("118211002", "Bongbong"),
                        new GeoItem("118211004", "Kingking (Poblacion)"),
                        new GeoItem("118211012", "Las Arenas"),
                        new GeoItem("118211005", "Magnaga"),
                        new GeoItem("118211006", "Matiao"),
                        new GeoItem("118211007", "Napnapan"),
                        new GeoItem("118211003", "P. Fuentes"),
                        new GeoItem("118211009", "Tagdangua"),
                        new GeoItem("118211014", "Tag-Ugpo"),
                        new GeoItem("118211010", "Tambongon"),
                        new GeoItem("118211011", "Tibagon")
                    };
                case "112319000": // Tagum City
                    return new List<GeoItem>
                    {
                        new GeoItem("112319001", "Apokon"),
                        new GeoItem("112319003", "Bincungan"),
                        new GeoItem("112319004", "Busaon"),
                        new GeoItem("112319005", "Canocotan"),
                        new GeoItem("112319006", "Cuambogan"),
                        new GeoItem("112319007", "La Filipina"),
                        new GeoItem("112319008", "Liboganon"),
                        new GeoItem("112319009", "Madaum"),
                        new GeoItem("112319010", "Magdum"),
                        new GeoItem("112319023", "Magugpo East"),
                        new GeoItem("112319024", "Magugpo North"),
                        new GeoItem("112319016", "Magugpo Poblacion"),
                        new GeoItem("112319025", "Magugpo South"),
                        new GeoItem("112319026", "Magugpo West"),
                        new GeoItem("112319011", "Mankilam"),
                        new GeoItem("112319012", "New Balamban"),
                        new GeoItem("112319013", "Nueva Fuerza"),
                        new GeoItem("112319014", "Pagsabangan"),
                        new GeoItem("112319015", "Pandapan"),
                        new GeoItem("112319018", "San Agustin"),
                        new GeoItem("112319019", "San Isidro"),
                        new GeoItem("112319020", "San Miguel"),
                        new GeoItem("112319022", "Visayan Village")
                    };
                case "112315000": // Panabo City
                    return new List<GeoItem>
                    {
                        new GeoItem("112315001", "A. O. Floirendo"),
                        new GeoItem("112315003", "Buenavista"),
                        new GeoItem("112315004", "Cacao"),
                        new GeoItem("112315005", "Cagangohan"),
                        new GeoItem("112315006", "Consolacion"),
                        new GeoItem("112315007", "Dapco"),
                        new GeoItem("112315002", "Datu Abdul Dadia"),
                        new GeoItem("112315009", "Gredu (Poblacion)"),
                        new GeoItem("112315010", "J.P. Laurel"),
                        new GeoItem("112315011", "Kasilak"),
                        new GeoItem("112315012", "Katipunan"),
                        new GeoItem("112315013", "Katualan"),
                        new GeoItem("112315014", "Kauswagan"),
                        new GeoItem("112315015", "Kiotoy"),
                        new GeoItem("112315016", "Little Panay"),
                        new GeoItem("112315017", "Lower Panaga"),
                        new GeoItem("112315018", "Mabunao"),
                        new GeoItem("112315019", "Maduao"),
                        new GeoItem("112315020", "Malativas"),
                        new GeoItem("112315021", "Manay"),
                        new GeoItem("112315022", "Nanyo"),
                        new GeoItem("112315023", "New Malaga"),
                        new GeoItem("112315024", "New Malitbog"),
                        new GeoItem("112315025", "New Pandan (Poblacion)"),
                        new GeoItem("112315026", "New Visayas"),
                        new GeoItem("112315027", "Quezon"),
                        new GeoItem("112315028", "Salvacion"),
                        new GeoItem("112315029", "San Francisco (Poblacion)"),
                        new GeoItem("112315030", "San Nicolas"),
                        new GeoItem("112315041", "San Pedro"),
                        new GeoItem("112315031", "San Roque"),
                        new GeoItem("112315032", "San Vicente"),
                        new GeoItem("112315033", "Santa Cruz"),
                        new GeoItem("112315034", "Santo Niño (Poblacion)"),
                        new GeoItem("112315035", "Sindaton"),
                        new GeoItem("112315036", "Southern Davao"),
                        new GeoItem("112315037", "Tagpore"),
                        new GeoItem("112315038", "Tibungol"),
                        new GeoItem("112315039", "Upper Licanan"),
                        new GeoItem("112315040", "Waterfall")
                    };
                case "112317000": // Island Garden City of Samal
                    return new List<GeoItem>
                    {
                        new GeoItem("112317005", "Balet"),
                        new GeoItem("112317007", "Caliclic"),
                        new GeoItem("112317008", "Camudmud"),
                        new GeoItem("112317011", "Cogon"),
                        new GeoItem("112317017", "Kinawitnon"),
                        new GeoItem("112317019", "Libuak"),
                        new GeoItem("112317024", "Mambago-B"),
                        new GeoItem("112317025", "Miranda (Poblacion)"),
                        new GeoItem("112317026", "Moncado (Poblacion)"),
                        new GeoItem("112317030", "San Agustin"),
                        new GeoItem("112317031", "San Antonio"),
                        new GeoItem("112317032", "San Isidro (Babak)"),
                        new GeoItem("112317038", "Santo Niño"),
                        new GeoItem("112317044", "Tagpopongan"),
                        new GeoItem("112317045", "Tambo"),
                        new GeoItem("112317046", "Toril"),
                        new GeoItem("112317003", "Aumbay"),
                        new GeoItem("112317004", "Aundanao"),
                        new GeoItem("112317009", "Catagman"),
                        new GeoItem("112317010", "Cawag"),
                        new GeoItem("112317014", "Del Monte"),
                        new GeoItem("112317015", "Guilon"),
                        new GeoItem("112317020", "Licup"),
                        new GeoItem("112317021", "Limao"),
                        new GeoItem("112317023", "Mambago-A"),
                        new GeoItem("112317028", "Peñaplata (Poblacion)"),
                        new GeoItem("112317034", "San Jose"),
                        new GeoItem("112317035", "San Miguel"),
                        new GeoItem("112317041", "Tagbay"),
                        new GeoItem("112317042", "Tagbitan-ag"),
                        new GeoItem("112317043", "Tagdaliao"),
                        new GeoItem("112317001", "Adecor"),
                        new GeoItem("112317002", "Anonang"),
                        new GeoItem("112317006", "Bandera"),
                        new GeoItem("112317012", "Cogon (Talicud)"),
                        new GeoItem("112317013", "Dadatan"),
                        new GeoItem("112317016", "Kanaan"),
                        new GeoItem("112317018", "Libertad"),
                        new GeoItem("112317022", "Linosutan"),
                        new GeoItem("112317027", "Pangubatan"),
                        new GeoItem("112317029", "Poblacion (Kaputian)"),
                        new GeoItem("112317033", "San Isidro (Kaputian)"),
                        new GeoItem("112317036", "San Remigio"),
                        new GeoItem("112317039", "Sion"),
                        new GeoItem("112317037", "Santa Cruz"),
                        new GeoItem("112317040", "Tagbaobo")
                    };
                case "112301000": // Asuncion
                    return new List<GeoItem>
                    {
                        new GeoItem("112301004", "Binancian"),
                        new GeoItem("112301006", "Buan"),
                        new GeoItem("112301007", "Buclad"),
                        new GeoItem("112301008", "Cabaywa"),
                        new GeoItem("112301032", "Cambanogoy (Poblacion)"),
                        new GeoItem("112301009", "Camansa"),
                        new GeoItem("112301010", "Camoning"),
                        new GeoItem("112301011", "Canatan"),
                        new GeoItem("112301012", "Concepcion"),
                        new GeoItem("112301013", "Doña Andrea"),
                        new GeoItem("112301026", "Magatos"),
                        new GeoItem("112301028", "Napungas"),
                        new GeoItem("112301029", "New Bantayan"),
                        new GeoItem("112301040", "New Loon"),
                        new GeoItem("112301030", "New Santiago"),
                        new GeoItem("112301031", "Pamacaun"),
                        new GeoItem("112301034", "Sagayen"),
                        new GeoItem("112301036", "San Vicente"),
                        new GeoItem("112301037", "Santa Filomena"),
                        new GeoItem("112301039", "Sonlon")
                    };
                case "112323000": // Braulio E. Dujali
                    return new List<GeoItem>
                    {
                        new GeoItem("112323001", "Cabayangan"),
                        new GeoItem("112323002", "Dujali (Poblacion)"),
                        new GeoItem("112323003", "Magupising"),
                        new GeoItem("112323004", "New Casay"),
                        new GeoItem("112323005", "Tanglaw")
                    };
                case "112303000": // Carmen
                    return new List<GeoItem>
                    {
                        new GeoItem("112303001", "Alejal"),
                        new GeoItem("112303002", "Anibongan"),
                        new GeoItem("112303003", "Asuncion (Cuatro-Cuatro)"),
                        new GeoItem("112303005", "Cebulano"),
                        new GeoItem("112303006", "Guadalupe"),
                        new GeoItem("112303007", "Ising (Poblacion)"),
                        new GeoItem("112303008", "La Paz"),
                        new GeoItem("112303010", "Mabaus"),
                        new GeoItem("112303011", "Mabuhay"),
                        new GeoItem("112303012", "Magsaysay"),
                        new GeoItem("112303014", "Mangalcal"),
                        new GeoItem("112303015", "Minda"),
                        new GeoItem("112303016", "New Camiling"),
                        new GeoItem("112303023", "Salvacion"),
                        new GeoItem("112303018", "San Isidro"),
                        new GeoItem("112303019", "Santo Niño"),
                        new GeoItem("112303024", "Taba"),
                        new GeoItem("112303020", "Tibulao"),
                        new GeoItem("112303021", "Tubod"),
                        new GeoItem("112303022", "Tuganay")
                    };
                case "112305000": // Kapalong
                    return new List<GeoItem>
                    {
                        new GeoItem("112305008", "Capungagan"),
                        new GeoItem("112305005", "Florida"),
                        new GeoItem("112305006", "Gabuyan"),
                        new GeoItem("112305007", "Gupitan"),
                        new GeoItem("112305009", "Katipunan"),
                        new GeoItem("112305012", "Luna"),
                        new GeoItem("112305013", "Mabantao"),
                        new GeoItem("112305015", "Mamacao"),
                        new GeoItem("112305021", "Maniki (Poblacion)"),
                        new GeoItem("112305018", "Pag-asa"),
                        new GeoItem("112305022", "Sampao"),
                        new GeoItem("112305003", "Semong"),
                        new GeoItem("112305026", "Sua-on"),
                        new GeoItem("112305028", "Tiburcia")
                    };
                case "112314000": // New Corella
                    return new List<GeoItem>
                    {
                        new GeoItem("112314001", "Cabidianan"),
                        new GeoItem("112314002", "Carcor"),
                        new GeoItem("112314003", "Del Monte"),
                        new GeoItem("112314004", "Del Pilar"),
                        new GeoItem("112314005", "El Salvador"),
                        new GeoItem("112314006", "Limba-an"),
                        new GeoItem("112314007", "Macgum"),
                        new GeoItem("112314008", "Mambing"),
                        new GeoItem("112314009", "Mesaoy"),
                        new GeoItem("112314010", "New Bohol"),
                        new GeoItem("112314011", "New Cortez"),
                        new GeoItem("112314012", "New Sambog"),
                        new GeoItem("112314013", "Patrocenio"),
                        new GeoItem("112314014", "Poblacion"),
                        new GeoItem("112314020", "San Jose"),
                        new GeoItem("112314015", "San Roque"),
                        new GeoItem("112314016", "Santa Cruz"),
                        new GeoItem("112314017", "Santa Fe"),
                        new GeoItem("112314018", "Santo Niño"),
                        new GeoItem("112314019", "Suawon")
                    };
                case "112324000": // San Isidro
                    return new List<GeoItem>
                    {
                        new GeoItem("112324001", "Dacudao"),
                        new GeoItem("112324002", "Datu Balong"),
                        new GeoItem("112324003", "Igangon"),
                        new GeoItem("112324004", "Kipalili"),
                        new GeoItem("112324005", "Libuton"),
                        new GeoItem("112324006", "Linao"),
                        new GeoItem("112324007", "Mamangan"),
                        new GeoItem("112324008", "Monte Dujali"),
                        new GeoItem("112324009", "Pinamuno"),
                        new GeoItem("112324013", "Poblacion (Sawata Proper)"),
                        new GeoItem("112324010", "Sabangan"),
                        new GeoItem("112324011", "San Miguel"),
                        new GeoItem("112324012", "Santo Niño")
                    };
                case "112318000": // Santo Tomas
                    return new List<GeoItem>
                    {
                        new GeoItem("112318001", "Balagunan"),
                        new GeoItem("112318002", "Bobongon"),
                        new GeoItem("112318016", "Casig-ang"),
                        new GeoItem("112318003", "Esperanza"),
                        new GeoItem("112318004", "Kimamon"),
                        new GeoItem("112318005", "Kinamayan"),
                        new GeoItem("112318006", "La Libertad"),
                        new GeoItem("112318007", "Lungaog"),
                        new GeoItem("112318008", "Magwawa"),
                        new GeoItem("112318009", "New Katipunan"),
                        new GeoItem("112318017", "New Visayas"),
                        new GeoItem("112318010", "Pantaron"),
                        new GeoItem("112318018", "Salvacion"),
                        new GeoItem("112318013", "San Jose"),
                        new GeoItem("112318014", "San Miguel"),
                        new GeoItem("112318019", "San Vicente"),
                        new GeoItem("112318015", "Talomo"),
                        new GeoItem("112318011", "Tibal-og (Poblacion)"),
                        new GeoItem("112318020", "Tulalian")
                    };
                case "112322000": // Talaingod
                    return new List<GeoItem>
                    {
                        new GeoItem("112322001", "Dagohoy"),
                        new GeoItem("112322002", "Palma Gil"),
                        new GeoItem("112322003", "Santo Niño")
                    };
                case "112403000": // Digos City (26 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112403001", "Aplaya"),
                        new GeoItem("112403002", "Balabag"),
                        new GeoItem("112403009", "Binaton"),
                        new GeoItem("112403003", "Cogon"),
                        new GeoItem("112403010", "Colorado"),
                        new GeoItem("112403011", "Dawis"),
                        new GeoItem("112403004", "Dulangan"),
                        new GeoItem("112403005", "Goma"),
                        new GeoItem("112403012", "Igpit"),
                        new GeoItem("112403006", "Kapatagan (Rizal)"),
                        new GeoItem("112403013", "Kiagot"),
                        new GeoItem("112403014", "Mahayahay"),
                        new GeoItem("112403007", "Matti"),
                        new GeoItem("112403015", "Ruparan"),
                        new GeoItem("112403008", "San Agustin"),
                        new GeoItem("112403016", "San Jose (Balutakay)"),
                        new GeoItem("112403017", "San Miguel (Odaca)"),
                        new GeoItem("112403018", "San Roque"),
                        new GeoItem("112403019", "Sinawilan"),
                        new GeoItem("112403020", "Soong"),
                        new GeoItem("112403021", "Soong Proper"),
                        new GeoItem("112403022", "Tiguman"),
                        new GeoItem("112403023", "Tres De Mayo"),
                        new GeoItem("112403024", "Zone 1 (Poblacion)"),
                        new GeoItem("112403025", "Zone 2 (Poblacion)"),
                        new GeoItem("112403026", "Zone 3 (Poblacion)")
                    };
                case "112401000": // Bansalan (25 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112401001", "Alegre"),
                        new GeoItem("112401002", "Alta Vista"),
                        new GeoItem("112401003", "Anonang"),
                        new GeoItem("112401004", "Bitaug"),
                        new GeoItem("112401005", "Bonifacio"),
                        new GeoItem("112401006", "Buenavista"),
                        new GeoItem("112401007", "Darapuay"),
                        new GeoItem("112401008", "Dolo"),
                        new GeoItem("112401009", "Eman"),
                        new GeoItem("112401010", "Kinuskusan"),
                        new GeoItem("112401011", "Libertad"),
                        new GeoItem("112401012", "Linawan"),
                        new GeoItem("112401013", "Mabuhay"),
                        new GeoItem("112401014", "Mabunga"),
                        new GeoItem("112401015", "Managa"),
                        new GeoItem("112401016", "Marber"),
                        new GeoItem("112401017", "New Clarin"),
                        new GeoItem("112401018", "Poblacion Uno"),
                        new GeoItem("112401019", "Poblacion Dos"),
                        new GeoItem("112401020", "Rizal"),
                        new GeoItem("112401021", "Santo Niño"),
                        new GeoItem("112401022", "Sibayan"),
                        new GeoItem("112401023", "Tinongtongan"),
                        new GeoItem("112401024", "Tubod"),
                        new GeoItem("112401025", "Union")
                    };
                case "112404000": // Hagonoy (21 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112404001", "Aplaya"),
                        new GeoItem("112404002", "Balaceras"),
                        new GeoItem("112404003", "Claster"),
                        new GeoItem("112404004", "Guihing"),
                        new GeoItem("112404005", "Hagonoy Cruz"),
                        new GeoItem("112404006", "Kibuaya"),
                        new GeoItem("112404007", "La Union"),
                        new GeoItem("112404008", "Lapla"),
                        new GeoItem("112404009", "Lologtan"),
                        new GeoItem("112404010", "Malinao"),
                        new GeoItem("112404011", "Mount Carmel"),
                        new GeoItem("112404012", "Poblacion"),
                        new GeoItem("112404013", "Paligue"),
                        new GeoItem("112404014", "Sacub"),
                        new GeoItem("112404015", "San Guillermo"),
                        new GeoItem("112404016", "San Isidro"),
                        new GeoItem("112404017", "San Jose"),
                        new GeoItem("112404018", "Sinayawan"),
                        new GeoItem("112404019", "Tologan"),
                        new GeoItem("112404020", "Sacub Upper"),
                        new GeoItem("112404021", "Guihing Aplaya")
                    };
                case "112406000": // Kiblawan (30 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112406001", "Abnao"),
                        new GeoItem("112406002", "Bagumbayan"),
                        new GeoItem("112406003", "Balasiao"),
                        new GeoItem("112406004", "Bonifacio"),
                        new GeoItem("112406005", "Bulol-Salo"),
                        new GeoItem("112406006", "Bunot"),
                        new GeoItem("112406007", "Cogon-Bacaca"),
                        new GeoItem("112406008", "Dapok"),
                        new GeoItem("112406009", "Ihan"),
                        new GeoItem("112406010", "Kibongbong"),
                        new GeoItem("112406011", "Kimlawis"),
                        new GeoItem("112406012", "Kisulan"),
                        new GeoItem("112406013", "Lati-an"),
                        new GeoItem("112406014", "Manual"),
                        new GeoItem("112406015", "Maraga-a"),
                        new GeoItem("112406016", "Molopolo"),
                        new GeoItem("112406017", "New Sibonga"),
                        new GeoItem("112406018", "Panaglib"),
                        new GeoItem("112406019", "Pasig"),
                        new GeoItem("112406020", "Poblacion"),
                        new GeoItem("112406021", "Pocag"),
                        new GeoItem("112406022", "San Isidro"),
                        new GeoItem("112406023", "San Jose"),
                        new GeoItem("112406024", "San Pedro"),
                        new GeoItem("112406025", "Santo Niño"),
                        new GeoItem("112406026", "Tacub"),
                        new GeoItem("112406027", "Tacul"),
                        new GeoItem("112406028", "Waterfall"),
                        new GeoItem("112406029", "Kiblawan Proper"),
                        new GeoItem("112406030", "Paitan")
                    };
                case "112407000": // Magsaysay (22 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112407001", "Bacungan"),
                        new GeoItem("112407002", "Balnate"),
                        new GeoItem("112407003", "Barayong"),
                        new GeoItem("112407004", "Blocon"),
                        new GeoItem("112407005", "Dalawinon"),
                        new GeoItem("112407006", "Dalumay"),
                        new GeoItem("112407007", "Glamang"),
                        new GeoItem("112407008", "Kanapulo"),
                        new GeoItem("112407009", "Kasuga"),
                        new GeoItem("112407010", "Lower Bala"),
                        new GeoItem("112407011", "Mabini"),
                        new GeoItem("112407012", "Maibo"),
                        new GeoItem("112407013", "Malawanit"),
                        new GeoItem("112407014", "Malongon"),
                        new GeoItem("112407015", "New Ilocos"),
                        new GeoItem("112407016", "New Opo (New Opon)"),
                        new GeoItem("112407017", "Poblacion"),
                        new GeoItem("112407018", "San Isidro"),
                        new GeoItem("112407019", "San Miguel"),
                        new GeoItem("112407020", "Tacul"),
                        new GeoItem("112407021", "Tagaytay"),
                        new GeoItem("112407022", "Upper Bala")
                    };
                case "112408000": // Malalag (15 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112408001", "Bagumbayan"),
                        new GeoItem("112408002", "Baybay"),
                        new GeoItem("112408003", "Bolton"),
                        new GeoItem("112408004", "Bulacan"),
                        new GeoItem("112408005", "Caputian"),
                        new GeoItem("112408006", "Ibo"),
                        new GeoItem("112408007", "Kiblagon"),
                        new GeoItem("112408008", "Lapu-Lapu"),
                        new GeoItem("112408009", "Mabini"),
                        new GeoItem("112408010", "New Baclayon"),
                        new GeoItem("112408011", "Pitogo"),
                        new GeoItem("112408012", "Pitu"),
                        new GeoItem("112408013", "Poblacion"),
                        new GeoItem("112408014", "Tagulaje"),
                        new GeoItem("112408015", "San Isidro")
                    };
                case "112410000": // Matanao (26 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112410001", "Asbang"),
                        new GeoItem("112410002", "Asinan"),
                        new GeoItem("112410003", "Bagumbayan"),
                        new GeoItem("112410004", "Bangkal"),
                        new GeoItem("112410005", "Buas"),
                        new GeoItem("112410006", "Camanchiles"),
                        new GeoItem("112410007", "Ceboza"),
                        new GeoItem("112410008", "Colonsabak"),
                        new GeoItem("112410009", "Dongan-Pekong"),
                        new GeoItem("112410010", "Kabasagan"),
                        new GeoItem("112410011", "Kapok"),
                        new GeoItem("112410012", "Kauswagan"),
                        new GeoItem("112410013", "Kibao"),
                        new GeoItem("112410014", "La Suerte"),
                        new GeoItem("112410015", "Langa-an"),
                        new GeoItem("112410016", "Lower Marber"),
                        new GeoItem("112410017", "Manga"),
                        new GeoItem("112410018", "New Katipunan"),
                        new GeoItem("112410019", "Poblacion"),
                        new GeoItem("112410020", "Sabang"),
                        new GeoItem("112410021", "San Jose"),
                        new GeoItem("112410022", "San Miguel"),
                        new GeoItem("112410023", "San Vicente"),
                        new GeoItem("112410024", "Saub"),
                        new GeoItem("112410025", "Tibongbong"),
                        new GeoItem("112410026", "Towak")
                    };
                case "112411000": // Padada (17 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112411001", "Almendras"),
                        new GeoItem("112411002", "Don Sergio Osmeña, Sr."),
                        new GeoItem("112411003", "Harada Butai"),
                        new GeoItem("112411004", "Lower Katipunan"),
                        new GeoItem("112411005", "Lower Limonzo"),
                        new GeoItem("112411006", "Lower Malinao"),
                        new GeoItem("112411007", "N C Ordaneza District"),
                        new GeoItem("112411008", "Northern Paligue"),
                        new GeoItem("112411009", "Palili"),
                        new GeoItem("112411010", "Piape"),
                        new GeoItem("112411011", "Poblacion"),
                        new GeoItem("112411012", "San Isidro"),
                        new GeoItem("112411013", "Southern Paligue"),
                        new GeoItem("112411014", "Tulogan"),
                        new GeoItem("112411015", "Upper Limonzo"),
                        new GeoItem("112411016", "Upper Malinao"),
                        new GeoItem("112411017", "Punta Piape")
                    };
                case "112412000": // Santa Cruz (18 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112412001", "Astorga"),
                        new GeoItem("112412002", "Bato"),
                        new GeoItem("112412003", "Coronon"),
                        new GeoItem("112412004", "Darong"),
                        new GeoItem("112412005", "Inawayan"),
                        new GeoItem("112412006", "Jose Rizal"),
                        new GeoItem("112412007", "Matutungan"),
                        new GeoItem("112412008", "Melilia"),
                        new GeoItem("112412009", "Saliducon"),
                        new GeoItem("112412010", "Sibulan"),
                        new GeoItem("112412011", "Sinawilan"),
                        new GeoItem("112412012", "Tagabuli"),
                        new GeoItem("112412013", "Tibolo"),
                        new GeoItem("112412014", "Tuban"),
                        new GeoItem("112412015", "Zone I (Poblacion)"),
                        new GeoItem("112412016", "Zone II (Poblacion)"),
                        new GeoItem("112412017", "Zone III (Poblacion)"),
                        new GeoItem("112412018", "Zone IV (Poblacion)")
                    };
                case "112414000": // Sulop (25 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112414001", "Balasinon"),
                        new GeoItem("112414002", "Buguis"),
                        new GeoItem("112414003", "Carre"),
                        new GeoItem("112414004", "Claster"),
                        new GeoItem("112414005", "Harada Butai"),
                        new GeoItem("112414006", "Katipunan"),
                        new GeoItem("112414007", "Kiblawan"),
                        new GeoItem("112414008", "Labon"),
                        new GeoItem("112414009", "Laping"),
                        new GeoItem("112414010", "Los Amigos"),
                        new GeoItem("112414011", "Luparan"),
                        new GeoItem("112414012", "McKinley"),
                        new GeoItem("112414013", "New Cebu"),
                        new GeoItem("112414014", "Osmeña"),
                        new GeoItem("112414015", "Palili"),
                        new GeoItem("112414016", "Poblacion"),
                        new GeoItem("112414017", "Roxas"),
                        new GeoItem("112414018", "Solongvale"),
                        new GeoItem("112414019", "Tagolilong"),
                        new GeoItem("112414020", "Tala-o"),
                        new GeoItem("112414021", "Talas"),
                        new GeoItem("112414022", "Tanwalang"),
                        new GeoItem("112414023", "Waterfall"),
                        new GeoItem("112414024", "Zone 1"),
                        new GeoItem("112414025", "Zone 2")
                    };
                case "118601000": // Don Marcelino (15 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("118601001", "Baluntaya"),
                        new GeoItem("118601002", "Calian"),
                        new GeoItem("118601003", "Dalupan"),
                        new GeoItem("118601004", "Kinanga"),
                        new GeoItem("118601005", "Kiobog"),
                        new GeoItem("118601006", "Lanao"),
                        new GeoItem("118601007", "Lapuan"),
                        new GeoItem("118601008", "Lawa"),
                        new GeoItem("118601009", "Linadasan"),
                        new GeoItem("118601010", "Mabuhay"),
                        new GeoItem("118601011", "North Lamidan"),
                        new GeoItem("118601012", "Nueva Villa"),
                        new GeoItem("118601013", "South Lamidan"),
                        new GeoItem("118601014", "Talagutong"),
                        new GeoItem("118601015", "West Lamidan")
                    };
                case "118602000": // Jose Abad Santos (26 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("118602001", "Balangonan"),
                        new GeoItem("118602002", "Buguis"),
                        new GeoItem("118602003", "Bukid"),
                        new GeoItem("118602004", "Butuan"),
                        new GeoItem("118602005", "Butulan"),
                        new GeoItem("118602006", "Caburan Big"),
                        new GeoItem("118602007", "Caburan Small (Poblacion)"),
                        new GeoItem("118602008", "Camalian"),
                        new GeoItem("118602009", "Carahayan"),
                        new GeoItem("118602010", "Cayaponga"),
                        new GeoItem("118602011", "Culaman"),
                        new GeoItem("118602012", "Kalbay"),
                        new GeoItem("118602013", "Kitayo"),
                        new GeoItem("118602014", "Magulibas"),
                        new GeoItem("118602015", "Malalan"),
                        new GeoItem("118602016", "Mangile"),
                        new GeoItem("118602017", "Marabutuan"),
                        new GeoItem("118602018", "Meybio"),
                        new GeoItem("118602019", "Molmol"),
                        new GeoItem("118602020", "Nuing"),
                        new GeoItem("118602021", "Patulang"),
                        new GeoItem("118602022", "Quiapo"),
                        new GeoItem("118602023", "San Isidro"),
                        new GeoItem("118602024", "Sugal"),
                        new GeoItem("118602025", "Tabayon"),
                        new GeoItem("118602026", "Tanuman")
                    };
                case "118603000": // Malita (30 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("118603001", "Bito"),
                        new GeoItem("118603002", "Bolila"),
                        new GeoItem("118603003", "Buhangin"),
                        new GeoItem("118603004", "Culaman"),
                        new GeoItem("118603005", "Datu Danwata"),
                        new GeoItem("118603006", "Demoloc"),
                        new GeoItem("118603007", "Felis"),
                        new GeoItem("118603008", "Fishing Village"),
                        new GeoItem("118603009", "Kibalatong"),
                        new GeoItem("118603010", "Kidalapong"),
                        new GeoItem("118603011", "Kilalag"),
                        new GeoItem("118603012", "Kinangan"),
                        new GeoItem("118603013", "Lacaron"),
                        new GeoItem("118603014", "Lagumit"),
                        new GeoItem("118603015", "Lais"),
                        new GeoItem("118603016", "Little Baguio"),
                        new GeoItem("118603017", "Macol"),
                        new GeoItem("118603018", "Mana"),
                        new GeoItem("118603019", "Manuel Peralta"),
                        new GeoItem("118603020", "New Argao"),
                        new GeoItem("118603021", "Pangaleon"),
                        new GeoItem("118603022", "Pangian"),
                        new GeoItem("118603023", "Pinalpalan"),
                        new GeoItem("118603024", "Poblacion"),
                        new GeoItem("118603025", "Sangay"),
                        new GeoItem("118603026", "Talogoy"),
                        new GeoItem("118603027", "Tical"),
                        new GeoItem("118603028", "Ticulon"),
                        new GeoItem("118603029", "Tingolo"),
                        new GeoItem("118603030", "Tubalan")
                    };
                case "118604000": // Santa Maria (22 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("118604001", "Basiawan"),
                        new GeoItem("118604002", "Buca"),
                        new GeoItem("118604003", "Cadaatan"),
                        new GeoItem("118604004", "Datu Daligasao"),
                        new GeoItem("118604005", "Datu Intan"),
                        new GeoItem("118604006", "Kidadan"),
                        new GeoItem("118604007", "Kinilidan"),
                        new GeoItem("118604008", "Kisulad"),
                        new GeoItem("118604009", "Malalag Tubig"),
                        new GeoItem("118604010", "Mamacao"),
                        new GeoItem("118604011", "Ogpao"),
                        new GeoItem("118604012", "Poblacion"),
                        new GeoItem("118604013", "Pongpong"),
                        new GeoItem("118604014", "San Agustin"),
                        new GeoItem("118604015", "San Antonio"),
                        new GeoItem("118604016", "San Isidro"),
                        new GeoItem("118604017", "San Juan"),
                        new GeoItem("118604018", "San Pedro"),
                        new GeoItem("118604019", "San Roque"),
                        new GeoItem("118604020", "Santo Niño"),
                        new GeoItem("118604021", "Santo Rosario"),
                        new GeoItem("118604022", "Tanglad")
                    };
                case "118605000": // Sarangani (12 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("118605001", "Batuganding"),
                        new GeoItem("118605002", "Camahual"),
                        new GeoItem("118605003", "Camalig"),
                        new GeoItem("118605004", "Gomtago"),
                        new GeoItem("118605005", "Konel"),
                        new GeoItem("118605006", "Laker (Sarangani Sur)"),
                        new GeoItem("118605007", "Lipol"),
                        new GeoItem("118605008", "Mabila (Poblacion)"),
                        new GeoItem("118605009", "Patuco (Sarangani Norte)"),
                        new GeoItem("118605010", "Paraiso"),
                        new GeoItem("118605011", "Tagen"),
                        new GeoItem("118605012", "Tinina")
                    };
                case "112509000": // City of Mati (26 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112509001", "Badas"),
                        new GeoItem("112509002", "Bobon"),
                        new GeoItem("112509003", "Buso"),
                        new GeoItem("112509004", "Cabuaya"),
                        new GeoItem("112509005", "Central (City Proper)"),
                        new GeoItem("112509006", "Culian"),
                        new GeoItem("112509007", "Dahican"),
                        new GeoItem("112509008", "Danao"),
                        new GeoItem("112509009", "Dawan"),
                        new GeoItem("112509010", "Don Enrique Lopez"),
                        new GeoItem("112509011", "Don Martin Marundan"),
                        new GeoItem("112509012", "Don Salvador Lopez, Sr."),
                        new GeoItem("112509013", "Langka"),
                        new GeoItem("112509014", "Lawigan"),
                        new GeoItem("112509015", "Libudon"),
                        new GeoItem("112509016", "Luban"),
                        new GeoItem("112509017", "Macambol"),
                        new GeoItem("112509018", "Mamali"),
                        new GeoItem("112509019", "Matiao"),
                        new GeoItem("112509020", "Mayo"),
                        new GeoItem("112509021", "Sainz"),
                        new GeoItem("112509022", "Sanghay"),
                        new GeoItem("112509023", "Tagabakid"),
                        new GeoItem("112509024", "Tagbinonga"),
                        new GeoItem("112509025", "Taguibo"),
                        new GeoItem("112509026", "Tamisan")
                    };
                case "112501000": // Baganga (18 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112501001", "Baculin"),
                        new GeoItem("112501002", "Ban-ao (Banao)"),
                        new GeoItem("112501003", "Batawan"),
                        new GeoItem("112501004", "Batiano"),
                        new GeoItem("112501005", "Binondo"),
                        new GeoItem("112501006", "Bobonao"),
                        new GeoItem("112501007", "Campawan"),
                        new GeoItem("112501008", "Central (Poblacion)"),
                        new GeoItem("112501009", "Dapnan"),
                        new GeoItem("112501010", "Kinablangan"),
                        new GeoItem("112501011", "Lucod"),
                        new GeoItem("112501012", "Mahan-ub"),
                        new GeoItem("112501013", "Mikit"),
                        new GeoItem("112501014", "Sawa"),
                        new GeoItem("112501015", "San Isidro"),
                        new GeoItem("112501016", "San Victor"),
                        new GeoItem("112501017", "Saoquegue"),
                        new GeoItem("112501018", "Lambajon")
                    };
                case "112502000": // Banaybanay (14 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112502001", "Cabagayan"),
                        new GeoItem("112502002", "Cagawasan"),
                        new GeoItem("112502003", "Calapagan"),
                        new GeoItem("112502004", "Cambaleon"),
                        new GeoItem("112502005", "Cabubuhan"),
                        new GeoItem("112502006", "Maputi"),
                        new GeoItem("112502007", "Mogbongcogon"),
                        new GeoItem("112502008", "Panikian"),
                        new GeoItem("112502009", "Piso Proper"),
                        new GeoItem("112502010", "Poblacion"),
                        new GeoItem("112502011", "Punta Linao"),
                        new GeoItem("112502012", "Rang-ay"),
                        new GeoItem("112502013", "San Vicente"),
                        new GeoItem("112502014", "Mahayag")
                    };
                case "112503000": // Boston (8 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112503001", "Caatihan"),
                        new GeoItem("112503002", "Cabasagan"),
                        new GeoItem("112503003", "Carmen"),
                        new GeoItem("112503004", "Cawayanan"),
                        new GeoItem("112503005", "Poblacion"),
                        new GeoItem("112503006", "San Jose"),
                        new GeoItem("112503007", "Sibajay"),
                        new GeoItem("112503008", "Simulao")
                    };
                case "112504000": // Caraga (17 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112504001", "Alvar"),
                        new GeoItem("112504002", "Caningag"),
                        new GeoItem("112504003", "Don Leon Balante"),
                        new GeoItem("112504004", "Lamiawan"),
                        new GeoItem("112504005", "Manorigao"),
                        new GeoItem("112504006", "Mercedes"),
                        new GeoItem("112504007", "Palma Gil"),
                        new GeoItem("112504008", "Poblacion"),
                        new GeoItem("112504009", "San Jose"),
                        new GeoItem("112504010", "San Luis"),
                        new GeoItem("112504011", "San Miguel"),
                        new GeoItem("112504012", "San Pedro"),
                        new GeoItem("112504013", "Santa Fe"),
                        new GeoItem("112504014", "Santiago"),
                        new GeoItem("112504015", "Pichon"),
                        new GeoItem("112504016", "Sobrecarey"),
                        new GeoItem("112504017", "PM Sobrecarey")
                    };
                case "112505000": // Cateel (16 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112505001", "Abijod"),
                        new GeoItem("112505002", "Alegria"),
                        new GeoItem("112505003", "Aliwagwag"),
                        new GeoItem("112505004", "Aragon"),
                        new GeoItem("112505005", "Baybay"),
                        new GeoItem("112505006", "Magagong"),
                        new GeoItem("112505007", "Mainit"),
                        new GeoItem("112505008", "Malibago"),
                        new GeoItem("112505009", "San Alfonso"),
                        new GeoItem("112505010", "San Antonio"),
                        new GeoItem("112505011", "San Miguel"),
                        new GeoItem("112505012", "San Rafael"),
                        new GeoItem("112505013", "San Vicente"),
                        new GeoItem("112505014", "Santa Filomena"),
                        new GeoItem("112505015", "Taytayan"),
                        new GeoItem("112505016", "Poblacion")
                    };
                case "112506000": // Governor Generoso (20 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112506001", "Anitap"),
                        new GeoItem("112506002", "Crispin Dela Cruz"),
                        new GeoItem("112506003", "Don Aurelio Chicote"),
                        new GeoItem("112506004", "Lavigan"),
                        new GeoItem("112506005", "Luzon"),
                        new GeoItem("112506006", "Magdug"),
                        new GeoItem("112506007", "Manuel Roxas"),
                        new GeoItem("112506008", "Monserrat"),
                        new GeoItem("112506009", "Nangan"),
                        new GeoItem("112506010", "Oregon"),
                        new GeoItem("112506011", "Poblacion"),
                        new GeoItem("112506012", "Pundaguitan"),
                        new GeoItem("112506013", "Sergio Osmeña"),
                        new GeoItem("112506014", "Surop"),
                        new GeoItem("112506015", "Tagabebe"),
                        new GeoItem("112506016", "Tamban"),
                        new GeoItem("112506017", "Tandang Sora"),
                        new GeoItem("112506018", "Tibanban"),
                        new GeoItem("112506019", "Tiblawan"),
                        new GeoItem("112506020", "Upper Tibanban")
                    };
                case "112507000": // Lupon (21 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112507001", "Bagumbayan"),
                        new GeoItem("112507002", "Cabadiangan"),
                        new GeoItem("112507003", "Calapagan"),
                        new GeoItem("112507004", "Cocornon"),
                        new GeoItem("112507005", "Corporacion"),
                        new GeoItem("112507006", "Don Mariano Marcos"),
                        new GeoItem("112507007", "Ilangay"),
                        new GeoItem("112507008", "Langka"),
                        new GeoItem("112507009", "Lantawan"),
                        new GeoItem("112507010", "Limbahan"),
                        new GeoItem("112507011", "Macangao"),
                        new GeoItem("112507012", "Magsaysay"),
                        new GeoItem("112507013", "Mahayahay"),
                        new GeoItem("112507014", "Maragatas"),
                        new GeoItem("112507015", "Marayag"),
                        new GeoItem("112507016", "New Visayas"),
                        new GeoItem("112507017", "Poblacion"),
                        new GeoItem("112507018", "San Isidro"),
                        new GeoItem("112507019", "San Jose"),
                        new GeoItem("112507020", "Tagboa"),
                        new GeoItem("112507021", "Tagugpo")
                    };
                case "112508000": // Manay (17 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112508001", "Capasnan"),
                        new GeoItem("112508002", "Cayawan"),
                        new GeoItem("112508003", "Central (Poblacion)"),
                        new GeoItem("112508004", "Concepcion"),
                        new GeoItem("112508005", "Del Pilar"),
                        new GeoItem("112508006", "Guza"),
                        new GeoItem("112508007", "Holy Cross"),
                        new GeoItem("112508008", "Lambog"),
                        new GeoItem("112508009", "Mabini"),
                        new GeoItem("112508010", "Manreza"),
                        new GeoItem("112508011", "Old Macopa"),
                        new GeoItem("112508012", "Rizal"),
                        new GeoItem("112508013", "San Fermin"),
                        new GeoItem("112508014", "San Ignacio"),
                        new GeoItem("112508015", "San Isidro"),
                        new GeoItem("112508016", "Taocanga"),
                        new GeoItem("112508017", "Zaragoza")
                    };
                case "112510000": // San Isidro (16 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112510001", "Baon"),
                        new GeoItem("112510002", "Bitaogan"),
                        new GeoItem("112510003", "Cambaleon"),
                        new GeoItem("112510004", "Dugmanon"),
                        new GeoItem("112510005", "Iba"),
                        new GeoItem("112510006", "La Union"),
                        new GeoItem("112510007", "Lapu-lapu"),
                        new GeoItem("112510008", "Maag"),
                        new GeoItem("112510009", "Manikling"),
                        new GeoItem("112510010", "Maputi"),
                        new GeoItem("112510011", "San Miguel"),
                        new GeoItem("112510012", "San Roque"),
                        new GeoItem("112510013", "Santo Rosario"),
                        new GeoItem("112510014", "Sudlon"),
                        new GeoItem("112510015", "Talisay"),
                        new GeoItem("112510016", "Poblacion")
                    };
                case "112511000": // Tarragona (10 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112511001", "Cabagayan"),
                        new GeoItem("112511002", "Central (Poblacion)"),
                        new GeoItem("112511003", "Dadong"),
                        new GeoItem("112511004", "Jovellar"),
                        new GeoItem("112511005", "Limot"),
                        new GeoItem("112511006", "Lucatan"),
                        new GeoItem("112511007", "Magdug"),
                        new GeoItem("112511008", "Ompao"),
                        new GeoItem("112511009", "Tubaon"),
                        new GeoItem("112511010", "Tomoaong")
                    };
                case "112402101": // Poblacion District (40 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112402101001", "Barangay 1-A"),
                        new GeoItem("112402101002", "Barangay 2-A"),
                        new GeoItem("112402101003", "Barangay 3-A"),
                        new GeoItem("112402101004", "Barangay 4-A"),
                        new GeoItem("112402101005", "Barangay 5-A"),
                        new GeoItem("112402101006", "Barangay 6-A"),
                        new GeoItem("112402101007", "Barangay 7-A"),
                        new GeoItem("112402101008", "Barangay 8-A"),
                        new GeoItem("112402101009", "Barangay 9-A"),
                        new GeoItem("112402101010", "Barangay 10-A"),
                        new GeoItem("112402101011", "Barangay 11-B"),
                        new GeoItem("112402101012", "Barangay 12-B"),
                        new GeoItem("112402101013", "Barangay 13-B"),
                        new GeoItem("112402101014", "Barangay 14-B"),
                        new GeoItem("112402101015", "Barangay 15-B"),
                        new GeoItem("112402101016", "Barangay 16-B"),
                        new GeoItem("112402101017", "Barangay 17-B"),
                        new GeoItem("112402101018", "Barangay 18-B"),
                        new GeoItem("112402101019", "Barangay 19-B"),
                        new GeoItem("112402101020", "Barangay 20-B"),
                        new GeoItem("112402101021", "Barangay 21-C"),
                        new GeoItem("112402101022", "Barangay 22-C"),
                        new GeoItem("112402101023", "Barangay 23-C"),
                        new GeoItem("112402101024", "Barangay 24-C"),
                        new GeoItem("112402101025", "Barangay 25-C"),
                        new GeoItem("112402101026", "Barangay 26-C"),
                        new GeoItem("112402101027", "Barangay 27-C"),
                        new GeoItem("112402101028", "Barangay 28-C"),
                        new GeoItem("112402101029", "Barangay 29-C"),
                        new GeoItem("112402101030", "Barangay 30-C"),
                        new GeoItem("112402101031", "Barangay 31-D"),
                        new GeoItem("112402101032", "Barangay 32-D"),
                        new GeoItem("112402101033", "Barangay 33-D"),
                        new GeoItem("112402101034", "Barangay 34-D"),
                        new GeoItem("112402101035", "Barangay 35-D"),
                        new GeoItem("112402101036", "Barangay 36-D"),
                        new GeoItem("112402101037", "Barangay 37-D"),
                        new GeoItem("112402101038", "Barangay 38-D"),
                        new GeoItem("112402101039", "Barangay 39-D"),
                        new GeoItem("112402101040", "Barangay 40-D")
                    };
                case "112402102": // Talomo District (14 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112402102001", "Bago Aplaya"),
                        new GeoItem("112402102002", "Bago Gallera"),
                        new GeoItem("112402102003", "Baliok"),
                        new GeoItem("112402102004", "Bucana"),
                        new GeoItem("112402102005", "Catalunan Grande"),
                        new GeoItem("112402102006", "Catalunan Pequeño"),
                        new GeoItem("112402102007", "Dumoy"),
                        new GeoItem("112402102008", "Langub"),
                        new GeoItem("112402102009", "Ma-a"),
                        new GeoItem("112402102010", "Magtuod"),
                        new GeoItem("112402102011", "Matina Aplaya"),
                        new GeoItem("112402102012", "Matina Crossing"),
                        new GeoItem("112402102013", "Matina Pangi"),
                        new GeoItem("112402102014", "Talomo Proper")
                    };
                case "112402201": // Agdao District (11 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112402201001", "Agdao Proper"),
                        new GeoItem("112402201002", "Centro (San Juan)"),
                        new GeoItem("112402201003", "Gov. Paciano Bangoy"),
                        new GeoItem("112402201004", "Gov. Vicente Duterte"),
                        new GeoItem("112402201005", "Kap. Tomas Monteverde, Sr."),
                        new GeoItem("112402201006", "Lapu-Lapu"),
                        new GeoItem("112402201007", "Leon Garcia"),
                        new GeoItem("112402201008", "Rafael Castillo"),
                        new GeoItem("112402201009", "San Antonio"),
                        new GeoItem("112402201010", "Ubalde"),
                        new GeoItem("112402201011", "Wilfredo Aquino")
                    };
                case "112402202": // Buhangin District (13 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112402202001", "Acacia"),
                        new GeoItem("112402202002", "Alfonso Angliongto Sr."),
                        new GeoItem("112402202003", "Buhangin Proper"),
                        new GeoItem("112402202004", "Cabantian"),
                        new GeoItem("112402202005", "Callawa"),
                        new GeoItem("112402202006", "Communal"),
                        new GeoItem("112402202007", "Indangan"),
                        new GeoItem("112402202008", "Mandug"),
                        new GeoItem("112402202009", "Pampanga"),
                        new GeoItem("112402202010", "Sasa"),
                        new GeoItem("112402202011", "Tigatto"),
                        new GeoItem("112402202012", "Vicente Hizon Sr."),
                        new GeoItem("112402202013", "Waan")
                    };
                case "112402203": // Bunawan District (9 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112402203001", "Alejandra Navarro (Lasang)"),
                        new GeoItem("112402203002", "Bunawan Proper"),
                        new GeoItem("112402203003", "Gatungan"),
                        new GeoItem("112402203004", "Ilang"),
                        new GeoItem("112402203005", "Mahayag"),
                        new GeoItem("112402203006", "Mudiang"),
                        new GeoItem("112402203007", "Panacan"),
                        new GeoItem("112402203008", "San Isidro (Licanan)"),
                        new GeoItem("112402203009", "Tibungco")
                    };
                case "112402204": // Paquibato District (13 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112402204001", "Colosas"),
                        new GeoItem("112402204002", "Fatima (Benowang)"),
                        new GeoItem("112402204003", "Lumiad"),
                        new GeoItem("112402204004", "Mabuhay"),
                        new GeoItem("112402204005", "Malabog"),
                        new GeoItem("112402204006", "Mapula"),
                        new GeoItem("112402204007", "Panalum"),
                        new GeoItem("112402204008", "Pandaitan"),
                        new GeoItem("112402204009", "Paquibato Proper"),
                        new GeoItem("112402204010", "Paradise Embak"),
                        new GeoItem("112402204011", "Salapawan"),
                        new GeoItem("112402204012", "Sumimao"),
                        new GeoItem("112402204013", "Tapak")
                    };
                case "112402301": // Baguio District (8 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112402301001", "Baguio Proper"),
                        new GeoItem("112402301002", "Cadalian"),
                        new GeoItem("112402301003", "Carmen"),
                        new GeoItem("112402301004", "Gumalang"),
                        new GeoItem("112402301005", "Malagos"),
                        new GeoItem("112402301006", "Tambobong"),
                        new GeoItem("112402301007", "Tawan-Tawan"),
                        new GeoItem("112402301008", "Wines")
                    };
                case "112402302": // Calinan District (19 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112402302001", "Biao Joaquin"),
                        new GeoItem("112402302002", "Calinan Proper"),
                        new GeoItem("112402302003", "Cawayan"),
                        new GeoItem("112402302004", "Dacudao"),
                        new GeoItem("112402302005", "Dalagdag"),
                        new GeoItem("112402302006", "Dominga"),
                        new GeoItem("112402302007", "Inayangan"),
                        new GeoItem("112402302008", "Lacson"),
                        new GeoItem("112402302009", "Lamanan"),
                        new GeoItem("112402302010", "Lampianao"),
                        new GeoItem("112402302011", "Megkawayan"),
                        new GeoItem("112402302012", "Pangyan"),
                        new GeoItem("112402302013", "Riverside"),
                        new GeoItem("112402302014", "Saloy"),
                        new GeoItem("112402302015", "Sirib"),
                        new GeoItem("112402302016", "Subasta"),
                        new GeoItem("112402302017", "Talomo River"),
                        new GeoItem("112402302018", "Tamayong"),
                        new GeoItem("112402302019", "Wangan")
                    };
                case "112402303": // Marilog District (12 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112402303001", "Baganihan"),
                        new GeoItem("112402303002", "Bantol"),
                        new GeoItem("112402303003", "Buda"),
                        new GeoItem("112402303004", "Dalag"),
                        new GeoItem("112402303005", "Datu Salumay"),
                        new GeoItem("112402303006", "Gumitan"),
                        new GeoItem("112402303007", "Magsaysay"),
                        new GeoItem("112402303008", "Malamba"),
                        new GeoItem("112402303009", "Marilog Proper"),
                        new GeoItem("112402303010", "Salaysay"),
                        new GeoItem("112402303011", "Suawan (Tuli)"),
                        new GeoItem("112402303012", "Tamugan")
                    };
                case "112402304": // Toril District (25 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112402304001", "Alambre"),
                        new GeoItem("112402304002", "Atan-Awe"),
                        new GeoItem("112402304003", "Bangkas Heights"),
                        new GeoItem("112402304004", "Baracatan"),
                        new GeoItem("112402304005", "Bato"),
                        new GeoItem("112402304006", "Bayabas"),
                        new GeoItem("112402304007", "Binugao"),
                        new GeoItem("112402304008", "Camansi"),
                        new GeoItem("112402304009", "Catigan"),
                        new GeoItem("112402304010", "Crossing Bayabas"),
                        new GeoItem("112402304011", "Daliao"),
                        new GeoItem("112402304012", "Daliaon Plantation"),
                        new GeoItem("112402304013", "Eden"),
                        new GeoItem("112402304014", "Kilate"),
                        new GeoItem("112402304015", "Lizada"),
                        new GeoItem("112402304016", "Lubogan"),
                        new GeoItem("112402304017", "Marapangi"),
                        new GeoItem("112402304018", "Mulig"),
                        new GeoItem("112402304019", "Sibulan"),
                        new GeoItem("112402304020", "Sirawan"),
                        new GeoItem("112402304021", "Tagluno"),
                        new GeoItem("112402304022", "Tagurano"),
                        new GeoItem("112402304023", "Tibuloy"),
                        new GeoItem("112402304024", "Toril Proper"),
                        new GeoItem("112402304025", "Tungkalan")
                    };
                case "112402305": // Tugbok District (18 barangays)
                    return new List<GeoItem>
                    {
                        new GeoItem("112402305001", "Angalan"),
                        new GeoItem("112402305002", "Bago Oshiro"),
                        new GeoItem("112402305003", "Balenggaeng"),
                        new GeoItem("112402305004", "Biao Escuela"),
                        new GeoItem("112402305005", "Biao Guinga"),
                        new GeoItem("112402305006", "Los Amigos"),
                        new GeoItem("112402305007", "Manambulan"),
                        new GeoItem("112402305008", "Manuel Guianga"),
                        new GeoItem("112402305009", "Matina Biao"),
                        new GeoItem("112402305010", "Mintal"),
                        new GeoItem("112402305011", "New Carmen"),
                        new GeoItem("112402305012", "New Valencia"),
                        new GeoItem("112402305013", "Santo Niño"),
                        new GeoItem("112402305014", "Tacunan"),
                        new GeoItem("112402305015", "Tagakpan"),
                        new GeoItem("112402305016", "Talandang"),
                        new GeoItem("112402305017", "Tugbok Proper"),
                        new GeoItem("112402305018", "Ula")
                    };
                default:
                    return new List<GeoItem>();
            }
        }

    }
}
