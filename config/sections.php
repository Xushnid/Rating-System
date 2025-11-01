<?php

return [
    'groups' => [
        'group_1' => [
            'name' => 'Ilmiy salohiyat',
            'sections' => ['1.1', '1.3']
        ],
        'group_2' => [
            'name' => 'Nashrlar va iqtiboslar',
            'sections' => ['2.1.1', '2.1.2', '2.1.3', '2.1.4', '2.2', '2.3', '2.4', '2.5', '2.6.1', '2.6.2']
        ],
        'group_3' => [
            'name' => 'O\'quv-uslubiy va ilmiy ishlar',
            'sections' => ['3.1.1', '3.1.2', '3.1.3', '3.2.1', '3.2.2', '3.3', '3.4', '3.5']
        ],
        'group_4' => [
            'name' => 'Xalqaro hamkorlik',
            'sections' => ['4.1', '4.2', '4.3', '4.4']
        ],
        'group_5' => [
            'name' => 'Iqtidorli talabalar',
            'sections' => ['5.1', '5.2']
        ],
    ],

    'sections' => [
        // ====== GROUP 1: Ilmiy salohiyat (Mavjud bo'limlar) ======
        '1.1' => [
            'name' => 'Himoya samaradorligi',
            'table' => 'submissions',
            'has_file' => false,
            'has_common_fields' => false,
            'score_calculation' => ['method' => 'count', 'value' => 1],
            'fields' => [
                'specialty_code_name' => ['label' => 'Ixtisoslik shifri va nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'dissertation_topic' => ['label' => 'Dissertatsiya mavzusi', 'type' => 'textarea', 'required' => true, 'in_table' => false],
                'council_code' => ['label' => 'Maxsus kengash shifri', 'type' => 'text', 'required' => true, 'in_table' => true],
                'council_decision_number' => ['label' => 'Kengash qarori / raqami', 'type' => 'text', 'required' => true, 'in_table' => true],
                'decision_date' => ['label' => 'Kengash qarori sanasi', 'type' => 'date', 'required' => true, 'in_table' => true],
            ],
        ],
        '1.3' => [
            'name' => 'Xirsh indeksi (h-indeks) ≥ 5 bo‘lgan professor-o‘qituvchilar',
            'table' => 'submissions', 'has_file' => false, 'has_common_fields' => false,
            'score_calculation' => ['method' => 'field_value', 'field' => 'h_index'],
            'fields' => [
                'h_index' => ['label' => 'Xirsh indeksi (h-indeks)', 'type' => 'number', 'required' => true, 'in_table' => true],
                'data_period' => ['label' => 'Ma’lumotlar olingan davr (Yil)', 'type' => 'number', 'required' => true, 'in_table' => true],
                'url' => ['label' => 'Elektron internet manzili (Scopus, Google Scholar...)', 'type' => 'url', 'required' => true, 'in_table' => false],
            ],
        ],

        // ====== GROUP 2: Nashrlar va iqtiboslar (Mavjud va Yangi bo'limlar) ======
        '2.1.1' => [
            'name' => 'Q2 kvartil jurnallaridagi maqolalar',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => true,
            'score_calculation' => ['method' => 'share'],
            'fields' => [
                'journal_country' => ['label' => 'Nashr etilgan davlat nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'journal_name' => ['label' => 'Ilmiy jurnal nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
            ],
        ],
        '2.1.2' => [ // YANGI
            'name' => 'Q3 kvartil jurnallaridagi maqolalar',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => true,
            'score_calculation' => ['method' => 'share'],
            'fields' => [
                'journal_country' => ['label' => 'Nashr etilgan davlat nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'journal_name' => ['label' => 'Ilmiy jurnal nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
            ],
        ],
        '2.1.3' => [ // YANGI
            'name' => 'Q4 kvartil jurnallaridagi maqolalar',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => true,
            'score_calculation' => ['method' => 'share'],
            'fields' => [
                'journal_country' => ['label' => 'Nashr etilgan davlat nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'journal_name' => ['label' => 'Ilmiy jurnal nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
            ],
        ],
        '2.1.4' => [ // YANGI
            'name' => '“Web of Science”, “Scopus”da indekslanuvchi konferensiyalarda chop etilgan maqolalar',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => true,
            'score_calculation' => ['method' => 'share'],
            'fields' => [
                'journal_country' => ['label' => 'Nashr etilgan davlat nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'journal_name' => ['label' => 'Konferensiya nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
            ],
        ],
        '2.2' => [
            'name' => 'Xorijiy (OAK) jurnallar',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => true,
            'score_calculation' => ['method' => 'share'],
            'fields' => [
                'country' => ['label' => 'Davlat', 'type' => 'text', 'required' => true, 'in_table' => true],
                'journal_name' => ['label' => 'Jurnal nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
            ],
        ],
        '2.3' => [
            'name' => 'Respublika (OAK) jurnallar',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => true,
            'score_calculation' => ['method' => 'share'],
            'fields' => [
                'journal_name' => ['label' => 'Jurnal nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
            ],
        ],
        '2.4' => [
            'name' => 'Xalqaro konferensiyalar',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => true,
            'score_calculation' => ['method' => 'share'],
            'fields' => [
                'country' => ['label' => 'Davlat', 'type' => 'text', 'required' => true, 'in_table' => true],
                'conference_name' => ['label' => 'Konferensiya nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
            ],
        ],
        '2.5' => [
            'name' => 'Respublika konferensiyalari',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => true,
            'score_calculation' => ['method' => 'share'],
            'fields' => [
                'conference_name' => ['label' => 'Konferensiya nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'location' => ['label' => 'Joy (shahri)', 'type' => 'text', 'required' => true, 'in_table' => true],
            ],
        ],
        '2.6.1' => [ // YANGI
            'name' => '“Web of Science”, “Scopus” iqtiboslar',
            'table' => 'submissions', 'has_file' => false, 'has_common_fields' => false,
            'score_calculation' => ['method' => 'field_value', 'field' => 'citation_count'],
            'fields' => [
                'journal_name' => ['label' => 'Jurnal nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'publish_date' => ['label' => 'Nashr sanasi (oy va yil)', 'type' => 'text', 'required' => true, 'in_table' => true],
                'article_name' => ['label' => 'Maqola nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'publish_lang' => ['label' => 'Chop etilgan til', 'type' => 'text', 'required' => true, 'in_table' => false],
                'url' => ['label' => 'Link', 'type' => 'url', 'required' => true, 'in_table' => false],
                'citation_count' => ['label' => 'Iqtibos soni', 'type' => 'number', 'required' => true, 'in_table' => true],
            ],
        ],
        '2.6.2' => [ // YANGI
            'name' => '“Google Scholar” iqtiboslar',
            'table' => 'submissions', 'has_file' => false, 'has_common_fields' => false,
            'score_calculation' => ['method' => 'field_value', 'field' => 'citation_count'],
            'fields' => [
                'journal_name' => ['label' => 'Jurnal nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'publish_date' => ['label' => 'Nashr sanasi (oy va yil)', 'type' => 'text', 'required' => true, 'in_table' => true],
                'article_name' => ['label' => 'Maqola nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'publish_lang' => ['label' => 'Chop etilgan til', 'type' => 'text', 'required' => true, 'in_table' => false],
                'url' => ['label' => 'Link', 'type' => 'url', 'required' => true, 'in_table' => false],
                'citation_count' => ['label' => 'Iqtibos soni', 'type' => 'number', 'required' => true, 'in_table' => true],
            ],
        ],

         // ====== GROUP 3: O'quv-uslubiy va ilmiy ishlar (Yangi bo'limlar) ======
        '3.1.1' => [ // O'ZGARTIRILDI
            'name' => 'Darslik',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => false, // false ga o'zgartirildi
            'score_calculation' => ['method' => 'share'],
            'fields' => [
                'specialty_code_name' => ['label' => 'Ixtisoslik shifri va nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'article_name' => ['label' => 'Darslik nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'cert_number' => ['label' => 'Guvohnoma raqami', 'type' => 'text', 'required' => true, 'in_table' => true],
                'cert_date' => ['label' => 'Guvohnoma sanasi', 'type' => 'date', 'required' => true, 'in_table' => true],
                'authors_count' => ['label' => 'Mualliflar soni', 'type' => 'number', 'required' => true, 'in_table' => false],
                'share' => ['label' => 'Ulush', 'type' => 'number', 'required' => true, 'in_table' => true],
            ],
        ],
        '3.1.2' => [ // O'ZGARTIRILDI
            'name' => 'O‘quv qo‘llanma',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => false, // false ga o'zgartirildi
            'score_calculation' => ['method' => 'share'],
            'fields' => [
                'specialty_code_name' => ['label' => 'Ixtisoslik shifri va nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'article_name' => ['label' => 'O\'quv qo\'llanma nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'cert_number' => ['label' => 'Guvohnoma raqami', 'type' => 'text', 'required' => true, 'in_table' => true],
                'cert_date' => ['label' => 'Guvohnoma sanasi', 'type' => 'date', 'required' => true, 'in_table' => true],
                'authors_count' => ['label' => 'Mualliflar soni', 'type' => 'number', 'required' => true, 'in_table' => false],
                'share' => ['label' => 'Ulush', 'type' => 'number', 'required' => true, 'in_table' => true],
            ],
        ],
        '3.1.3' => [ // O'ZGARTIRILDI
            'name' => 'Monografiya',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => false, // false ga o'zgartirildi
            'score_calculation' => ['method' => 'share'],
            'fields' => [
                'specialty_code_name' => ['label' => 'Ixtisoslik shifri va nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'article_name' => ['label' => 'Monografiya nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'council_decision' => ['label' => 'Kengash bayoni, sanasi', 'type' => 'text', 'required' => true, 'in_table' => false],
                'publisher_name' => ['label' => 'Nashriyot nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'isbn' => ['label' => 'ISBN', 'type' => 'text', 'required' => true, 'in_table' => true],
                'authors_count' => ['label' => 'Mualliflar soni', 'type' => 'number', 'required' => true, 'in_table' => false],
                'share' => ['label' => 'Ulush', 'type' => 'number', 'required' => true, 'in_table' => true],
            ],
        ],
        '3.2.1' => [ // O'ZGARTIRILDI
            'name' => 'Patentlar (ixtiro, foydali model, sanoat namunalari va seleksiya yutuqlari)',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => false, // false ga o'zgartirildi
            'score_calculation' => ['method' => 'share'],
            'fields' => [
                'article_name' => ['label' => 'Patent berilgan ishlanmaning nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'issue_date' => ['label' => 'Berilgan sanasi', 'type' => 'date', 'required' => true, 'in_table' => true],
                'reg_number' => ['label' => 'Qayd raqami', 'type' => 'text', 'required' => true, 'in_table' => true],
                'authors_count' => ['label' => 'Mualliflar soni', 'type' => 'number', 'required' => true, 'in_table' => false],
                'share' => ['label' => 'Ulush', 'type' => 'number', 'required' => true, 'in_table' => true],
            ],
        ],
        '3.2.2' => [ // O'ZGARTIRILDI
            'name' => 'DGU/EHM guvohnomalari',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => false, // false ga o'zgartirildi
            'score_calculation' => ['method' => 'share'],
            'fields' => [
                'article_name' => ['label' => 'Berilgan material nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'issue_date' => ['label' => 'Berilgan sanasi', 'type' => 'date', 'required' => true, 'in_table' => true],
                'reg_number' => ['label' => 'Qayd raqami', 'type' => 'text', 'required' => true, 'in_table' => true],
                'authors_count' => ['label' => 'Mualliflar soni', 'type' => 'number', 'required' => true, 'in_table' => false],
                'share' => ['label' => 'Ulushi', 'type' => 'number', 'required' => true, 'in_table' => true],
            ],
        ],
        
        // Bu bo'limlarda o'zgarish yo'q, shunday qoladi
        '3.3' => [
            'name' => 'Xorijiy Grant mablag’lari',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => false,
            'score_calculation' => ['method' => 'field_value', 'field' => 'grant_sum'],
            'fields' => [
                'grant_name' => ['label' => 'Grant yoki buyurtma nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'foreign_grant_count' => ['label' => 'Xorijiy grantlar soni', 'type' => 'number', 'required' => true, 'in_table' => true],
                'grant_sum' => ['label' => 'Summasi', 'type' => 'number', 'required' => true, 'in_table' => true],
            ],
        ],
        '3.4' => [
            'name' => 'Sohalar buyurtmalari asosida o‘tkazilgan ilmiy tadqiqotlar',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => false,
            'score_calculation' => ['method' => 'field_value', 'field' => 'order_sum'],
            'fields' => [
                'order_name' => ['label' => 'Buyurtma nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'order_sum' => ['label' => 'Summasi', 'type' => 'number', 'required' => true, 'in_table' => true],
            ],
        ],
        '3.5' => [
            'name' => 'Davlat ilmiy loyihalari',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => false,
            'score_calculation' => ['method' => 'field_value', 'field' => 'grant_count'],
            'fields' => [
                'grant_topic' => ['label' => 'Davlat granti mavzusi nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'grant_count' => ['label' => 'Davlat granti soni', 'type' => 'number', 'required' => true, 'in_table' => true],
                'grant_sum' => ['label' => 'Summasi', 'type' => 'number', 'required' => true, 'in_table' => true],
            ],
        ],
        
        // ====== GROUP 4: Xalqaro hamkorlik (Yangi bo'limlar) ======
        '4.1' => [
            'name' => 'Xorijiy o’qituvchi ulushi',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => false,
            'score_calculation' => ['method' => 'count', 'value' => 1],
            'fields' => [
                'teacher_name' => ['label' => 'Xorijiy o’qituvchi F.I.Sh', 'type' => 'text', 'required' => true, 'in_table' => true],
                'country' => ['label' => 'Davlat', 'type' => 'text', 'required' => true, 'in_table' => true],
                'specialty' => ['label' => 'Mutaxassisligi', 'type' => 'textarea', 'required' => true, 'in_table' => false],
                'subject' => ['label' => 'O‘zbekiston OTMida dars beradigan fani', 'type' => 'text', 'required' => true, 'in_table' => true],
                'basis' => ['label' => 'Asos (buyruq, qaror, shartnoma va boshqalar)', 'type' => 'text', 'required' => true, 'in_table' => false],
            ],
        ],
        '4.2' => [
            'name' => 'Xorijiy talabalar ulushi',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => false,
            'score_calculation' => ['method' => 'count', 'value' => 1],
            'fields' => [
                'student_name' => ['label' => 'Xorijiy talabaning F.I.Sh', 'type' => 'text', 'required' => true, 'in_table' => true],
                'country' => ['label' => 'Davlat', 'type' => 'text', 'required' => true, 'in_table' => true],
                'bachelor_field' => ['label' => 'Ta’lim yo‘nalishi shifri va nomi', 'type' => 'text', 'required' => false, 'in_table' => true],
                'master_field' => ['label' => 'Magistratura mutaxassisligi shifri va nomi', 'type' => 'text', 'required' => false, 'in_table' => false],
                'basis' => ['label' => 'Asos (buyruq, qaror, shartnoma va boshqalar)', 'type' => 'text', 'required' => true, 'in_table' => false],
            ],
        ],
        '4.3' => [
            'name' => 'Xorijiy ilmiy stajirovka, ma’ruza, seminar, trening va malaka oshirish',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => false,
            'score_calculation' => ['method' => 'count', 'value' => 1],
            'fields' => [
                'country' => ['label' => 'Xorijiy davlat nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'university_name' => ['label' => 'Xorijiy OTM nomi', 'type' => 'text', 'required' => true, 'in_table' => true],
                'specialty' => ['label' => 'Mutaxassisligi', 'type' => 'text', 'required' => true, 'in_table' => false],
                'activity_name' => ['label' => 'Faoliyat nomi (ma’ruza, seminar, ...)', 'type' => 'text', 'required' => true, 'in_table' => true],
                'duration' => ['label' => 'Muddati (boshlanish va tugash sanasi)', 'type' => 'text', 'required' => true, 'in_table' => false],
                'basis' => ['label' => 'Asos (buyruq, qaror, shartnoma va boshqalar)', 'type' => 'text', 'required' => true, 'in_table' => false],
            ],
        ],
        '4.4' => [
            'name' => 'Akademik almashuv dasturlari',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => false,
            'score_calculation' => ['method' => 'count', 'value' => 1],
            'fields' => [
                // Bu bo'lim maydonlari JS orqali dinamik yaratiladi
            ],
        ],
        
        // ====== GROUP 5: Iqtidorli talabalar (Yangi bo'limlar) ======
        '5.1' => [
            'name' => 'Xalqaro sport musobaqalari, olimpiadalar',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => false,
            'score_calculation' => ['method' => 'count', 'value' => 1],
            'fields' => [
                'student_name' => ['label' => 'Talabaning F.I.Sh.', 'type' => 'text', 'required' => true, 'in_table' => true],
                'competition_name_international' => ['label' => 'Xalqaro tanlov/musobaqa nomi', 'type' => 'text', 'required' => false, 'in_table' => true],
                'competition_name_subject' => ['label' => 'Fan olimpiadasi nomi', 'type' => 'text', 'required' => false, 'in_table' => true],
                'location_date' => ['label' => 'O’tkazilgan joy, sana', 'type' => 'text', 'required' => true, 'in_table' => false],
                'place_taken' => ['label' => 'Egallagan o‘rni', 'type' => 'number', 'required' => true, 'in_table' => true],
                'diploma_series' => ['label' => 'Diplom Seriyasi va raqami', 'type' => 'text', 'required' => true, 'in_table' => false],
                'comment' => ['label' => 'Izoh (majburiy emas)', 'type' => 'textarea', 'required' => false, 'in_table' => false],
            ],
        ],
        '5.2' => [
            'name' => 'Respublika sport musobaqalari, olimpiadalar',
            'table' => 'submissions', 'has_file' => true, 'has_common_fields' => false,
            'score_calculation' => ['method' => 'count', 'value' => 1],
            'fields' => [
                'student_name' => ['label' => 'Talabaning F.I.Sh.', 'type' => 'text', 'required' => true, 'in_table' => true],
                'competition_name_republic' => ['label' => 'Respublika tanlov/musobaqa nomi', 'type' => 'text', 'required' => false, 'in_table' => true],
                'competition_name_subject' => ['label' => 'Fan olimpiadasi nomi', 'type' => 'text', 'required' => false, 'in_table' => true],
                'location_date' => ['label' => 'O’tkazilgan joy, sana', 'type' => 'text', 'required' => true, 'in_table' => false],
                'place_taken' => ['label' => 'Egallagan o‘rni', 'type' => 'number', 'required' => true, 'in_table' => true],
                'diploma_series' => ['label' => 'Diplom Seriyasi va raqami', 'type' => 'text', 'required' => true, 'in_table' => false],
                'comment' => ['label' => 'Izoh (majburiy emas)', 'type' => 'textarea', 'required' => false, 'in_table' => false],
            ],
        ],
    ]
];