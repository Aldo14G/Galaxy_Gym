<?php
// Incluir archivo de conexión
require_once 'conexion.php';

// Variables para mensajes y datos del cliente
$mensaje = "";
$tipo_mensaje = "";
$cliente = null;
$membresia_vigente = false;

// Procesar búsqueda de cliente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['buscar'])) {
    $busqueda = trim($_POST['busqueda']);
    
    if (empty($busqueda)) {
        $mensaje = "Por favor, ingrese un ID o teléfono para buscar.";
        $tipo_mensaje = "error";
    } else {
        if ($conexion) {
            // Determinar si la búsqueda es por ID o por teléfono
            $es_numero = is_numeric($busqueda);
            
            if ($es_numero && strlen($busqueda) <= 5) {
                // Búsqueda por ID
                $sql = "SELECT c.cliente_id, c.nombre, c.apellido, c.telefono, c.email, c.direccion,
                      m.fecha_vencimiento, tm.nombre AS tipo_membresia
                      FROM clientes c
                      LEFT JOIN membresias m ON c.cliente_id = m.cliente_id
                      LEFT JOIN tipos_membresia tm ON m.tipo_membresia_id = tm.tipo_membresia_id
                      WHERE c.cliente_id = ? AND c.activo = 1
                      ORDER BY m.fecha_vencimiento DESC LIMIT 1";
                
                $stmt = mysqli_prepare($conexion, $sql);
                mysqli_stmt_bind_param($stmt, "i", $busqueda);
            } else {
                // Búsqueda por teléfono o nombre
                $busqueda_like = "%" . $busqueda . "%";
                $sql = "SELECT c.cliente_id, c.nombre, c.apellido, c.telefono, c.email, c.direccion,
                      m.fecha_vencimiento, tm.nombre AS tipo_membresia
                      FROM clientes c
                      LEFT JOIN membresias m ON c.cliente_id = m.cliente_id
                      LEFT JOIN tipos_membresia tm ON m.tipo_membresia_id = tm.tipo_membresia_id
                      WHERE (c.telefono LIKE ? OR c.nombre LIKE ? OR c.apellido LIKE ?) AND c.activo = 1
                      ORDER BY m.fecha_vencimiento DESC LIMIT 1";
                
                $stmt = mysqli_prepare($conexion, $sql);
                mysqli_stmt_bind_param($stmt, "sss", $busqueda_like, $busqueda_like, $busqueda_like);
            }
            
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($resultado) > 0) {
                $cliente = mysqli_fetch_assoc($resultado);
                
                // Verificar si la membresía está vigente
                $hoy = date('Y-m-d');
                if ($cliente['fecha_vencimiento'] && $cliente['fecha_vencimiento'] >= $hoy) {
                    $membresia_vigente = true;
                } else {
                    $membresia_vigente = false;
                }
            } else {
                $mensaje = "No se encontró ningún cliente con el ID o teléfono proporcionado.";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Error en la conexión a la base de datos: " . mysqli_connect_error();
            $tipo_mensaje = "error";
        }
    }
}

// Procesar registro de entrada
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar_entrada'])) {
    $cliente_id = $_POST['cliente_id'];
    
    if ($conexion) {
        // Verificar si ya tiene una entrada sin salida registrada hoy
        $sql_verificar = "SELECT asistencia_id FROM asistencias 
                         WHERE cliente_id = ? AND DATE(fecha_hora) = CURDATE() AND fecha_hora >= NOW() - INTERVAL 8 HOUR";
        $stmt_verificar = mysqli_prepare($conexion, $sql_verificar);
        mysqli_stmt_bind_param($stmt_verificar, "i", $cliente_id);
        mysqli_stmt_execute($stmt_verificar);
        $result_verificar = mysqli_stmt_get_result($stmt_verificar);
        
        if (mysqli_num_rows($result_verificar) > 0) {
            $mensaje = "Ya existe un registro de entrada para este cliente hoy.";
            $tipo_mensaje = "warning";
        } else {
            // Verificar membresía vigente
            $query_membresia = "SELECT m.fecha_vencimiento FROM membresias m
                               WHERE m.cliente_id = ? 
                               ORDER BY m.fecha_vencimiento DESC LIMIT 1";
            $stmt_membresia = mysqli_prepare($conexion, $query_membresia);
            mysqli_stmt_bind_param($stmt_membresia, "i", $cliente_id);
            mysqli_stmt_execute($stmt_membresia);
            $result_membresia = mysqli_stmt_get_result($stmt_membresia);
            
            if (mysqli_num_rows($result_membresia) > 0) {
                $membresia = mysqli_fetch_assoc($result_membresia);
                $hoy = date('Y-m-d');
                
                if ($membresia['fecha_vencimiento'] >= $hoy) {
                    // Registrar entrada
                    $sql_entrada = "INSERT INTO asistencias (cliente_id, fecha_hora) 
                                  VALUES (?, NOW())";
                    $stmt_entrada = mysqli_prepare($conexion, $sql_entrada);
                    mysqli_stmt_bind_param($stmt_entrada, "i", $cliente_id);
                    
                    if (mysqli_stmt_execute($stmt_entrada)) {
                        $mensaje = "Entrada registrada exitosamente para " . $_POST['nombre_cliente'] . ".";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al registrar la entrada: " . mysqli_error($conexion);
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "La membresía de este cliente está vencida. Debe renovar para poder ingresar.";
                    $tipo_mensaje = "warning";
                }
            } else {
                $mensaje = "Este cliente no tiene membresía registrada.";
                $tipo_mensaje = "warning";
            }
        }
    } else {
        $mensaje = "Error en la conexión a la base de datos: " . mysqli_connect_error();
        $tipo_mensaje = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Accesos - Galaxy Gym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #0066cc;
            --secondary-color: #3498db;
            --accent-color: #1a5276;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --gray-color: #95a5a6;
        }
        
        body {
            background: linear-gradient(150deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            padding: 2rem 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            margin-bottom: 2rem;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border-radius: 20px 20px 0 0;
        }
        
        h2 {
            color: var(--accent-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.75rem;
        }
        
        h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 2px;
        }
        
        .cat-paw {
            position: absolute;
            bottom: 10px;
            right: 10px;
            font-size: 3rem;
            color: rgba(0, 102, 204, 0.1);
            transform: rotate(15deg);
        }
        
        .gym-icon {
            color: var(--primary-color);
            font-size: 2rem;
            margin-right: 0.75rem;
            vertical-align: middle;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--accent-color);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.25);
        }
        
        .btn-entrada {
            background-color: #2ecc71;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-entrada:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }
        
        .btn-search {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-search:hover {
            background-color: #0052a3;
            transform: translateY(-2px);
        }
        
        .success-message {
            background-color: rgba(46, 204, 113, 0.1);
            color: #27ae60;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .error-message {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .warning-message {
            background-color: rgba(243, 156, 18, 0.1);
            color: #f39c12;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .alert-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
        }
        
        .client-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            border-left: 5px solid var(--primary-color);
            position: relative;
        }
        
        .client-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 0.5rem;
        }
        
        .badge-membresia {
            background-color: rgba(0, 102, 204, 0.1);
            color: var(--primary-color);
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            border-radius: 50rem;
            margin-bottom: 1rem;
            display: inline-block;
        }
        
        .vigente {
            background-color: rgba(46, 204, 113, 0.1);
            color: #27ae60;
        }
        
        .vencida {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .table thead {
            background-color: rgba(0, 102, 204, 0.1);
        }
        
        .table th {
            font-weight: 600;
            color: var(--accent-color);
            border-bottom: none;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background-color: rgba(0, 102, 204, 0.05);
        }
        
        .btn-back {
            position: fixed;
            top: 20px;
            left: 20px;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }
        
        .cat-icon-small {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.2rem;
            color: var(--gray-color);
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <a href="index.php" class="btn btn-light btn-back">
        <i class="fas fa-arrow-left"></i>
    </a>
    
    <div class="container">
        <div class="card animate__animated animate__fadeIn">
            <i class="fas fa-cat cat-paw"></i>
            <h2>
                <i class="fas fa-id-card gym-icon"></i>Control de Accesos
            </h2>
            
            <?php
            // Mostrar mensaje si existe
            if (!empty($mensaje)) {
                if ($tipo_mensaje == "success") {
                    echo '<div class="success-message">
                            <i class="fas fa-check-circle alert-icon"></i>
                            <span>' . $mensaje . '</span>
                          </div>';
                } elseif ($tipo_mensaje == "warning") {
                    echo '<div class="warning-message">
                            <i class="fas fa-exclamation-triangle alert-icon"></i>
                            <span>' . $mensaje . '</span>
                          </div>';
                } else {
                    echo '<div class="error-message">
                            <i class="fas fa-exclamation-circle alert-icon"></i>
                            <span>' . $mensaje . '</span>
                          </div>';
                }
            }
            ?>
            
            <div class="row">
                <div class="col-md-12">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="mb-4">
                        <div class="input-group">
                            <input type="text" class="form-control" name="busqueda" placeholder="Buscar por ID, nombre o teléfono..." value="<?php echo isset($_POST['busqueda']) ? $_POST['busqueda'] : ''; ?>">
                            <button type="submit" name="buscar" class="btn btn-search">
                                <i class="fas fa-search me-2"></i>Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php
            // Mostrar la información del cliente si se encontró
            if ($cliente) {
                $hoy = date('Y-m-d');
                $estado_clase = $membresia_vigente ? 'vigente' : 'vencida';
                $estado_texto = $membresia_vigente ? 'Vigente' : 'Vencida';
                
                echo '<div class="client-card">
                        <i class="fas fa-cat cat-icon-small"></i>
                        <div class="row">
                            <div class="col-md-6">
                                <h3 class="client-name">' . $cliente['nombre'] . ' ' . $cliente['apellido'] . ' <small class="text-muted">#' . $cliente['cliente_id'] . '</small></h3>
                                <span class="badge-membresia ' . $estado_clase . '">
                                    ' . ($cliente['tipo_membresia'] ?: 'Sin membresía') . ' - ' . $estado_texto . '
                                </span>
                                <div class="client-info">
                                    <p><i class="fas fa-phone me-2"></i>' . $cliente['telefono'] . '</p>';
                
                if ($cliente['email']) {
                    echo '<p><i class="fas fa-envelope me-2"></i>' . $cliente['email'] . '</p>';
                }
                
                if ($cliente['fecha_vencimiento']) {
                    echo '<p><i class="fas fa-calendar-alt me-2"></i>Vence: ' . date('d/m/Y', strtotime($cliente['fecha_vencimiento'])) . '</p>';
                }
                
                echo '</div>
                            </div>
                            <div class="col-md-6 d-flex align-items-center justify-content-end">
                                <form method="post" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" class="me-2">
                                    <input type="hidden" name="cliente_id" value="' . $cliente['cliente_id'] . '">
                                    <input type="hidden" name="nombre_cliente" value="' . $cliente['nombre'] . ' ' . $cliente['apellido'] . '">
                                    <button type="submit" name="registrar_entrada" class="btn btn-entrada" ' . (!$membresia_vigente ? 'disabled' : '') . '>
                                        <i class="fas fa-sign-in-alt me-2"></i>Registrar Entrada
                                    </button>
                                </form>
                            </div>
                        </div>';
                
                if (!$membresia_vigente) {
                    echo '<div class="warning-message mt-3">
                            <i class="fas fa-exclamation-triangle alert-icon"></i>
                            <span>La membresía de este cliente está vencida. Debe renovar para poder ingresar.</span>
                          </div>';
                }
                
                echo '</div>';
                
                // Mostrar los accesos recientes del cliente
                $sql_accesos = "SELECT fecha_hora 
                              FROM asistencias 
                              WHERE cliente_id = ? 
                              ORDER BY fecha_hora DESC 
                              LIMIT 5";
                $stmt_accesos = mysqli_prepare($conexion, $sql_accesos);
                mysqli_stmt_bind_param($stmt_accesos, "i", $cliente['cliente_id']);
                mysqli_stmt_execute($stmt_accesos);
                $result_accesos = mysqli_stmt_get_result($stmt_accesos);
                
                if (mysqli_num_rows($result_accesos) > 0) {
                    echo '<h4 class="mt-4">Accesos recientes</h4>
                          <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                    </tr>
                                </thead>
                                <tbody>';
                    
                    while ($acceso = mysqli_fetch_assoc($result_accesos)) {
                        echo '<tr>
                                <td>' . date('d/m/Y', strtotime($acceso['fecha_hora'])) . '</td>
                                <td>' . date('h:i A', strtotime($acceso['fecha_hora'])) . '</td>
                              </tr>';
                    }
                    
                    echo '</tbody>
                        </table>
                      </div>';
                } else {
                    echo '<div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle me-2"></i>
                            Este cliente no tiene registros de acceso previos.
                          </div>';
                }
            }
            ?>
        </div>
        
        <div class="card animate__animated animate__fadeIn">
            <i class="fas fa-paw cat-paw"></i>
            <h2>
                <i class="fas fa-list gym-icon"></i>Registro de Accesos de Hoy
            </h2>
            
            <?php
            if ($conexion) {
                // Mostrar los accesos del día
                $sql_accesos_hoy = "SELECT a.asistencia_id, c.cliente_id, CONCAT(c.nombre, ' ', c.apellido) AS nombre_cliente, a.fecha_hora 
                                  FROM asistencias a
                                  JOIN clientes c ON a.cliente_id = c.cliente_id
                                  WHERE DATE(a.fecha_hora) = CURDATE()
                                  ORDER BY a.fecha_hora DESC";
                
                $result_accesos_hoy = mysqli_query($conexion, $sql_accesos_hoy);
                
                if ($result_accesos_hoy && mysqli_num_rows($result_accesos_hoy) > 0) {
                    echo '<div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID Cliente</th>
                                        <th>Nombre</th>
                                        <th>Hora de Entrada</th>
                                    </tr>
                                </thead>
                                <tbody>';
                    
                    while ($acceso = mysqli_fetch_assoc($result_accesos_hoy)) {
                        echo '<tr>
                                <td>' . $acceso['cliente_id'] . '</td>
                                <td>' . $acceso['nombre_cliente'] . '</td>
                                <td>' . date('h:i A', strtotime($acceso['fecha_hora'])) . '</td>
                              </tr>';
                    }
                    
                    echo '</tbody>
                        </table>
                      </div>';
                } else {
                    echo '<div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No hay registros de acceso para hoy.
                          </div>';
                }
            }
            ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>