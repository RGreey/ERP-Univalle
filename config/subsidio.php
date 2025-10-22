<?php

return [
    'timezone' => 'America/Bogota',

    // Cortes
    'hora_limite_cancelar'     => '13:00',
    'hora_limite_deshacer'     => '10:00',
    'cancelacion_normal_hasta' => '09:00',
    'cancelacion_tardia_hasta' => '13:00',

    // Ofertas standby
    'oferta_ttl_min'          => 15,
    'standby_parallel_offers' => 5,

    // Días hábiles
    'dias_habiles_iso' => [1,2,3,4,5],

    // Standby: permitir también a no beneficiarios (externos a la lista de beneficiarios)
    // true = cualquiera con registro standby activo puede recibir ofertas
    // false = solo quienes tengan PostulacionSubsidio en estados válidos
    'standby_allow_externals' => true,

    // Estados de PostulacionSubsidio que consideras “beneficiario” para priorización
    'standby_valid_states' => ['beneficiario','aprobada'],
];