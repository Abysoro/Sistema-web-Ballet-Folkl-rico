<?php
header('Content-Type: application/json; charset=utf-8');

$escenario = isset($_GET['escenario']) ? intval($_GET['escenario']) : 1;

$respuesta = [];

switch ($escenario) {

    case 1:
        $respuesta = [
            "titulo" => "Escenario 1: Demanda baja",
            "total_integrantes" => 10,
            "asignados" => 8,
            "espera" => 2,
            "tiempo_promedio" => "5 min",
            "analisis" => "En este escenario la demanda es menor a la capacidad disponible. El flujo de atención es estable, la mayoría de los integrantes logra ser asignada y la cola presenta baja congestión.",
            "detalle" => [
                [
                    "nombre" => "Ana Quispe",
                    "estado" => "Asignado",
                    "motivo" => "Seleccionado por mayor puntaje y disponibilidad de cupo"
                ],
                [
                    "nombre" => "Luis Mamani",
                    "estado" => "Asignado",
                    "motivo" => "Cumple criterios y existe capacidad suficiente"
                ],
                [
                    "nombre" => "Pedro Flores",
                    "estado" => "En espera",
                    "motivo" => "Quedó en espera por menor prioridad"
                ]
            ]
        ];
    break;

    case 2:
        $respuesta = [
            "titulo" => "Escenario 2: Alta demanda",
            "total_integrantes" => 30,
            "asignados" => 15,
            "espera" => 15,
            "tiempo_promedio" => "30 min",
            "analisis" => "En este escenario la demanda supera la capacidad disponible. El sistema presenta mayor congestión, crece la cola de espera y aumenta el tiempo promedio de atención.",
            "detalle" => [
                [
                    "nombre" => "Carlos Choque",
                    "estado" => "Asignado",
                    "motivo" => "Mayor puntaje ponderado"
                ],
                [
                    "nombre" => "Rosa Lima",
                    "estado" => "En espera",
                    "motivo" => "No alcanzó cupo por alta demanda"
                ],
                [
                    "nombre" => "María Condori",
                    "estado" => "En espera",
                    "motivo" => "Otros candidatos tuvieron mayor prioridad"
                ]
            ]
        ];
    break;

    case 3:
        $respuesta = [
            "titulo" => "Escenario 3: Prioridad por nivel",
            "total_integrantes" => 20,
            "asignados" => 10,
            "espera" => 10,
            "tiempo_promedio" => "20 min",
            "analisis" => "En este escenario el sistema prioriza a los integrantes con mayor nivel. Esto mejora el ajuste al perfil requerido, pero reduce la oportunidad de asignación de niveles inferiores.",
            "detalle" => [
                [
                    "nombre" => "Mario Orellana",
                    "estado" => "Asignado",
                    "motivo" => "Nivel avanzado y mayor puntaje en el criterio nivel"
                ],
                [
                    "nombre" => "Julia Pérez",
                    "estado" => "En espera",
                    "motivo" => "Nivel menor frente al requisito priorizado"
                ],
                [
                    "nombre" => "Elena Rojas",
                    "estado" => "Asignado",
                    "motivo" => "Mejor ajuste al nivel requerido"
                ]
            ]
        ];
    break;

    case 4:
        $respuesta = [
            "titulo" => "Escenario 4: Prioridad por equidad",
            "total_integrantes" => 20,
            "asignados" => 10,
            "espera" => 10,
            "tiempo_promedio" => "25 min",
            "analisis" => "En este escenario el sistema favorece la equidad, dando mayor prioridad a integrantes que no fueron asignados anteriormente. Esto mejora la distribución de oportunidades dentro del elenco.",
            "detalle" => [
                [
                    "nombre" => "Elena Vargas",
                    "estado" => "Asignado",
                    "motivo" => "Priorizado por equidad al haber quedado en espera anteriormente"
                ],
                [
                    "nombre" => "Juan Ticona",
                    "estado" => "En espera",
                    "motivo" => "Ya tenía participaciones previas"
                ],
                [
                    "nombre" => "Lucía Ramos",
                    "estado" => "Asignado",
                    "motivo" => "Mayor prioridad por menor experiencia previa"
                ]
            ]
        ];
    break;

    default:
        $respuesta = [
            "titulo" => "Escenario no válido",
            "total_integrantes" => 0,
            "asignados" => 0,
            "espera" => 0,
            "tiempo_promedio" => "0 min",
            "analisis" => "No se seleccionó un escenario válido.",
            "detalle" => []
        ];
    break;
}

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
?>