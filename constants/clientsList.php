<?php
// here you can find all availables
// CLIENTS NAME lists according to IT's ID fetched from the requests.

$clients = [
    /**
     * panneau solaire clients
     */
    "pannsol#1"    => "LEAD VALUE",        // (LEAD VALUE panneau solaire)
    "pannsol#2"    => "DATA OPP",          // (Data Opp panneau solaire)
    "pannsol#3"    => "SOFANMEDIA",        // (SOFANMEDIA panneau solaire)
    "pannsol#4"    => "AM BUSNESS",        // for CPF Bureautique (AM BUSNESS)
    "pannsol#5"    => "Goracash",          // (Goracash panneau solaire)
    "pannsol#6"    => "Unitead",           // (Unitead panneau solaire)
    "pannsol#7"    => "Adkomo",            // (Akdomo panneau solaire)
    "pannsol#8"    => "Oceads",            // (Oceads panneau solaire)
    "pannsol#9"    => "Mediamoov",         // (Mediamoov panneau solaire)
    "pannsol#10"   => "Yacuza",            // (Yacuza panneau solaire)
    "pannsol#11"   => "PERSEE MEDIA",      // (PERSEE MEDIA Sheet panneau solaire)
    "pannsol#12"   => "Batiweb",           // (Batiweb panneau solaire)
    "pannsol#13"   => "PERSEE MEDIA 2",    // (PERSEE MEDIA WS panneau solaire)
    "pannsol#14"   => "Lead Creative",     // (Lead Creative panneau solaire)
    "pannsol#15"   => "CONFLUENT DIGITAL", // (CONFLUENT DIGITAL panneau solaire)
    "pannsol#16"   => "Ted Jordan Srl",    // (Ted Jordan Srl panneau solaire)
    "pannsol#17"   => "Flexylead",         // (Flexylead panneau solaire)
    "pannsol#18"   => "Aston Group",       // (ASTON GROUP panneau solaire pv)
    "pannsol#19"   => "Leads FR",          // (Leads FR panneau solaire)
    "pannsol#20"   => "viteundevis",
    "pannsol#21"   => "LEAD VALUE",        // (LEAD VALUE panneau solaire)
    "pannsol#22"   => "PERFUSION DIGITAL", // (PERFUSION DIGITAL panneau solaire)
    "pannsol#23"   => "CPRY_DIGITAL",      //    "pannsol CPRY_DIGITAL"
    "pansol#24"    => "prosperaleads",     // (prosperaleads panneau solaire)
    /**
     * defiscalisation/Pinel clients
     */
    "defisc#1"     => "MyOptin",
    "defisc#2"     => "Lead Creative",           // (Lead Creative Defisc)
    "defisc#3"     => "VMB INVESTISSEMENTS SAS", // (VMB Defisc)
    "defisc#4"     => "Edilead",                 // (Edilead Defisc)
    "defisc#5"     => "SOFANMEDIA",              // (Sofanmedia 1er client Defisc)
    "defisc#6"     => "SOFANMEDIA 2",            // (Sofanmedia 2e client Defisc)
    "defisc#7"     => "CPRY_DIGITAL",            //AZUR
    /**
     * ENI clients
     */
    "energy#1"     => "Lead Creative",
    "energy#2"     => "EURO CRM",
    /**
     * poele a granules clients
     */
    "pag#1"        => "DATA OPP", // (Data Opp poele a granules)
    /**
     * isolation
     */
    "iso#1"        => "DATA OPP",          // (Data Opp isolation)
    "iso#2"        => "MOKHTAR",           // (MOKHTAR isolation)
    "iso#3"        => "Yacuza",            // (Yacuza isolation)
    "iso#4"        => "SH CONSEIL",        // (ex SOCIETE MOONER isolation)
    "iso#5"        => "CONFLUENT DIGITAL", // (CONFLUENT DIGITAL isolation)
    "iso#6"        => "Aston Group",       // (ASTON GROUP isolation)
    "iso#7"        => "Leads FR",          // (Leads FR isolation)
    "iso#8"        => "viteundevis",
    "iso#9"        => "PERFUSION DIGITAL",
    "iso#10"       => "CONFLUENT DIGITAL", //confluent digital isolation nouveaux
    "iso#11"       => "CPRY_DIGITAL",
    "iso#12"       => "Flexylead", //ite flexyleads
    "iso#13"       => "prosperaleads", //prosperaleads isolation

    /**
     * assurance
     */
    "assurance#1"  => "Lead Creative",   // (Lead creative assurance pret)
    "assurance#2"  => "SOFANMEDIA",      // (Sofanmedia assurance vie)
    "assurance#3"  => "EURO CRM",        // (EURO CRM/Axa assurance auto)
    "assurance#4"  => "Filiassur",       // (Filiassur/IKI assurance emprunteur)
    "assurance#5"  => "Filiassur",       // (Filiassur/IKI mutuelle senior)
    "assurance#6"  => "PERSEE MEDIA",    // (PERSEE MEDIA mutuelle senior)
    "assurance#7"  => "Oceads",          // (Oceads mutuelle senior)
    "assurance#8"  => "Mediamoov",       // (Mediamoov bilan auditif)
    "assurance#9"  => "Cardata",         // (Cardata bilan auditif)
    "assurance#10" => "Mediamoov 217",   // (Mediamoov 217 bilan auditif)
    "assurance#11" => "Mutac",           // (Mutac Assurance Obsèques)
    "assurance#12" => "LMP Sante",       // (LMP Santé)
    "assurance#13" => "Leads FR",        // (Leads FR mutuelle Santé Sénior)
    "assurance#14" => "Aston Group",     // (Aston Group mutuelle Santé Sénior)
    "assurance#15" => "Aston Group",     // (Aston Group ASSURANCE AUTO)
    "assurance#16" => "Test Client",     // (Test Client ASSURANCE ANIMAUX)
    "assurance#17" => "EURO CRM",        // (Ws-conciergerie assurance emprunteur)
    "assurance#18" => "EURO CRM",        // (euro crm mutuelle senior)
    "assurance#19" => "Mediamoov",       //Media Moov
    "assurance#20" => "auxillaire_veto", // for CPF Langue (SOFANMEDIA)
    "assurance#21" => "webrivage",       // (webrivage mutuelle senior)
    "assurance#22" => "CAP",             // for CPF Langue (SOFANMEDIA)
    "assurance#23" => "Mediamoov",       //Media Moov assurance emprunteur
    "assurance#24" => "Flexylead",       //Mutuelle senior de Flexylead
    "assurance#25" => "LEAD VALUE",
    "assurance#26" => "Lead Creative",     //Mutuelle senior de Lead Creative
    "assurance#27" => "CONFLUENT DIGITAL", //Mutuelle senior de "CONFLUENT DIGITAL"
    "assurance#28" => "DATA OPP",
    "assurance#29" => "test",
    "assurance#30" => "CONFLUENT DIGITAL", //animaux de "CONFLUENT DIGITAL"
    /**
     * security
     */
    "security#1"   => "Sector Alarm",
    "security#2"   => "Aston Group", // (Aston Group Alarm IDF)
    "security#3"   => "Aston Group", // (Aston Group Alarm NATIO)
    "security#4"   => "compleo",

    /**
     * formations
     */
    "formation#1"  => "AM BUSNESS", // for CPF Bureautique (AM BUSNESS)
    "formation#2"  => "AUXILLAIRE", // for CPF Langue (SOFANMEDIA)
    "formation#3"  => "AUXILLAIRE", // for CPF Langue (SOFANMEDIA)
    /**
     * pompe a chaleur
     */
    "pac#1"        => "LEAD VALUE",              // (LEAD VALUE pompe a chaleur)
    "pac#2"        => "VMB INVESTISSEMENTS SAS", // (VMB INVESTISSEMENTS SAS pompe a chaleur)
    "pac#3"        => "Goracash",                // (GORACASH pompe a chaleur)
    "pac#4"        => "DATA OPP",                // (Data Opp pompe a chaleur)
    "pac#5"        => "Edilead",                 // (Edilead pompe a chaleur)
    "pac#6"        => "Adkomo",                  // (Adkomo pompe a chaleur)
    "pac#7"        => "SOFANMEDIA",              // (SOFANMEDIA pompe a chaleur)
    "pac#8"        => "PROXISERVE",              // (PROXISERVE pompe a chaleur)
    "pac#9"        => "Unitead",                 // (Unitead pompe a chaleur)
    "pac#10"       => "Oceads",                  // (Oceads pompe a chaleur)
    "pac#11"       => "Mailomedia",              // (Mailomedia pompe a chaleur)
    "pac#12"       => "Yacuza",                  // (Yacuza pompe a chaleur)
    "pac#13"       => "Batiweb",                 // (Batiweb pompe a chaleur)
    "pac#14"       => "Ted Jordan Srl",          // (Ted Jordan Srl pompe a chaleur)
    "pac#15"       => "Flexylead",               // (Flexylead pompe a chaleur)
    "pac#16"       => "Leads FR",                // (Leads FR pompe a chaleur)
    "pac#17"       => "lovvis-ads",              // (lovvis-ads proxiserve pompe a chaleur-teletech)
    "pac#18"       => "lovvis-ads",              // (lovvis-ads proxiserve pompe a chaleur)
    "pac#19"       => "Aston Group",             // (Aston Group pompe a chaleur)
    "pac#20"       => "viteundevis",
    "pac#21"       => "LEAD VALUE",        // (LEAD VALUE pompe a chaleur)
    "pac#22"       => "CONFLUENT DIGITAL", //(CONFLUENT DIGITAL pompe a chaleur)
    "pac#23"       => "PERFUSION DIGITAL",
    "pac#24"       => "CPRY_DIGITAL", //PAC
    "pac#25"       => "Mediamoov",
    "pac#26"       => "prosperaleads", //prosperaleads pompe a chaleur
    /**
     * rachat de credits
     */
    "rac#1"        => "SOFANMEDIA", // (SOFANMEDIA rachat de credits)
    /**
     * Douche senior
     */
    "douche#1"     => "Lead Creative", // (Lead Creative Douche senior data)
    "douche#2"     => "Goracash",      // (Goracash Douche senior data)
    "douche#3"     => "viteundevis",
    "douche#4"     => "CONFLUENT DIGITAL",
    /**
     * Fenêtre
     */
    "fenetre#1"    => "Aston Group", // (Aston Group fenêtre)
    "fenetre#2"    => "Goracash",    // (Goracash fenêtre)
    "fenetre#3"    => "viteundevis",

    "clim#1"       => "CONFLUENT DIGITAL",
];
