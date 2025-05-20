<?php
require_once 'conexion.php';

$mensaje = "";
$tipo_mensaje = "";
$cliente = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar'])) {
    $nombre = $_POST["nombre"];
    $apellido = $_POST["apellido"];
    $telefono = $_POST["telefono"];
    $email = isset($_POST["email"]) ? $_POST["email"] : "";
    $direccion = isset($_POST["direccion"]) ? $_POST["direccion"] : "";
    $tipo_membresia_id = $_POST["tipo_membresia_id"];
    $metodo_pago_id = $_POST["metodo_pago_id"];
    $paga_inscripcion = isset($_POST["paga_inscripcion"]) ? 1 : 0;
    
    if (empty($nombre) || empty($apellido) || empty($telefono)) {
        $mensaje = "Por favor, complete los campos obligatorios (Nombre, Apellido, Teléfono).";
        $tipo_mensaje = "error";
    } else {
        if ($conexion) {
            $query = "CALL registrar_cliente_con_membresia(?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conexion, $query);
            mysqli_stmt_bind_param($stmt, "sssssiis", $nombre, $apellido, $telefono, $email, $direccion, $tipo_membresia_id, $metodo_pago_id, $paga_inscripcion);
            
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($result)) {
                    $nuevo_cliente_id = $row['nuevo_cliente_id'];
                    $mensaje = "¡Cliente registrado exitosamente! ID: " . $nuevo_cliente_id;
                    $tipo_mensaje = "success";
                    
                    $_POST = array();
                }
            } else {
                $mensaje = "Error al registrar el cliente: " . mysqli_error($conexion);
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Error en la conexión a la base de datos: " . mysqli_connect_error();
            $tipo_mensaje = "error";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['renovar'])) {
    $cliente_id = $_POST["cliente_id"];
    $tipo_membresia_id = $_POST["tipo_membresia_renovacion"];
    $metodo_pago_id = $_POST["metodo_pago_renovacion"];
    
    if ($conexion) {
        $query = "CALL renovar_membresia(?, ?, ?)";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "iii", $cliente_id, $tipo_membresia_id, $metodo_pago_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $fecha_inicio = $row['fecha_inicio'];
                $fecha_vencimiento = $row['fecha_vencimiento'];
                
                $mensaje = "¡Membresía renovada exitosamente! Vence el: " . date('d/m/Y', strtotime($fecha_vencimiento));
                $tipo_mensaje = "success";
            }
        } else {
            $mensaje = "Error al renovar la membresía: " . mysqli_error($conexion);
            $tipo_mensaje = "error";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['buscar'])) {
    $busqueda = trim($_POST['busqueda']);
    
    if (empty($busqueda)) {
        $mensaje = "Por favor, ingrese un término de búsqueda.";
        $tipo_mensaje = "error";
    } else {
        if ($conexion) {
            $es_numero = is_numeric($busqueda);
            
            if ($es_numero && strlen($busqueda) <= 5) {
                // Búsqueda por ID
                $sql = "SELECT c.cliente_id, c.nombre, c.apellido, c.telefono, c.email, c.direccion, c.activo,
                        m.fecha_inicio, m.fecha_vencimiento, tm.nombre AS tipo_membresia,
                        m.membresia_id, m.tipo_membresia_id
                        FROM clientes c
                        LEFT JOIN membresias m ON c.cliente_id = m.cliente_id
                        LEFT JOIN tipos_membresia tm ON m.tipo_membresia_id = tm.tipo_membresia_id
                        WHERE c.cliente_id = ?
                        ORDER BY m.fecha_vencimiento DESC 
                        LIMIT 1";
                
                $stmt = mysqli_prepare($conexion, $sql);
                mysqli_stmt_bind_param($stmt, "i", $busqueda);
            } else {

                $busqueda_like = "%" . $busqueda . "%";
                $sql = "SELECT c.cliente_id, c.nombre, c.apellido, c.telefono, c.email, c.direccion, c.activo,
                        m.fecha_inicio, m.fecha_vencimiento, tm.nombre AS tipo_membresia,
                        m.membresia_id, m.tipo_membresia_id
                        FROM clientes c
                        LEFT JOIN membresias m ON c.cliente_id = m.cliente_id
                        LEFT JOIN tipos_membresia tm ON m.tipo_membresia_id = tm.tipo_membresia_id
                        WHERE (c.nombre LIKE ? OR c.apellido LIKE ? OR c.telefono LIKE ?)
                        ORDER BY m.fecha_vencimiento DESC 
                        LIMIT 1";
                
                $stmt = mysqli_prepare($conexion, $sql);
                mysqli_stmt_bind_param($stmt, "sss", $busqueda_like, $busqueda_like, $busqueda_like);
            }
            
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($resultado) > 0) {
                $cliente = mysqli_fetch_assoc($resultado);
            } else {
                $mensaje = "No se encontró ningún cliente con los datos proporcionados.";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Error en la conexión a la base de datos: " . mysqli_connect_error();
            $tipo_mensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - Galaxy Gym</title>
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
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #0052a3;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background-color: #2ecc71;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }
        
        .btn-outline-secondary {
            color: var(--accent-color);
            border-color: #bdc3c7;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-outline-secondary:hover {
            background-color: #ecf0f1;
            color: var(--accent-color);
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
                <i class="fas fa-user-plus gym-icon"></i>Registro de Nuevo Cliente
            </h2>
            
            <?php
            // Mostrar mensaje si existe
            if (!empty($mensaje)) {
                if ($tipo_mensaje == "success") {
                    echo '<div class="success-message">
                            <i class="fas fa-check-circle alert-icon"></i>
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
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <label for="nombre" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo isset($_POST['nombre']) ? $_POST['nombre'] : ''; ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="apellido" class="form-label">Apellido *</label>
                        <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo isset($_POST['apellido']) ? $_POST['apellido'] : ''; ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="telefono" class="form-label">Teléfono *</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo isset($_POST['telefono']) ? $_POST['telefono'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo isset($_POST['direccion']) ? $_POST['direccion'] : ''; ?>">
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <label for="tipo_membresia_id" class="form-label">Tipo de Membresía *</label>
                        <select class="form-select" id="tipo_membresia_id" name="tipo_membresia_id" required>
                            <option value="">Seleccionar</option>
                            <?php
                            // Obtener tipos de membresía desde la base de datos
                            $query_membresias = "SELECT tipo_membresia_id, nombre, precio, incluye_inscripcion, precio_inscripcion FROM tipos_membresia ORDER BY precio";
                            $resultado_membresias = mysqli_query($conexion, $query_membresias);
                            
                            while ($membresia = mysqli_fetch_assoc($resultado_membresias)) {
                                $precio_texto = number_format($membresia['precio'], 2);
                                $inscripcion_info = $membresia['incluye_inscripcion'] ? "(incluye inscripción)" : "(+$" . number_format($membresia['precio_inscripcion'], 2) . " inscripción)";
                                echo '<option value="' . $membresia['tipo_membresia_id'] . '" ' . (isset($_POST['tipo_membresia_id']) && $_POST['tipo_membresia_id'] == $membresia['tipo_membresia_id'] ? 'selected' : '') . '>' . 
                                      $membresia['nombre'] . ' - $' . $precio_texto . ' ' . $inscripcion_info . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="metodo_pago_id" class="form-label">Método de Pago *</label>
                        <select class="form-select" id="metodo_pago_id" name="metodo_pago_id" required>
                            <option value="">Seleccionar</option>
                            <?php
                            // Obtener métodos de pago desde la base de datos
                            $query_pagos = "SELECT metodo_pago_id, nombre FROM metodos_pago ORDER BY nombre";
                            $resultado_pagos = mysqli_query($conexion, $query_pagos);
                            
                            while ($metodo = mysqli_fetch_assoc($resultado_pagos)) {
                                echo '<option value="' . $metodo['metodo_pago_id'] . '" ' . (isset($_POST['metodo_pago_id']) && $_POST['metodo_pago_id'] == $metodo['metodo_pago_id'] ? 'selected' : '') . '>' . 
                                      $metodo['nombre'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="paga_inscripcion" name="paga_inscripcion" <?php echo !isset($_POST['paga_inscripcion']) || $_POST['paga_inscripcion'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="paga_inscripcion">
                                Pagar inscripción (si aplica)
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="reset" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-times me-2"></i>Limpiar
                        </button>
                        <button type="submit" name="registrar" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Registrar Cliente
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="card animate__animated animate__fadeIn">
            <i class="fas fa-paw cat-paw"></i>
            <h2>
                <i class="fas fa-search gym-icon"></i>Buscar Cliente
            </h2>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="mb-4">
                <div class="input-group">
                    <input type="text" class="form-control" name="busqueda" placeholder="Buscar por ID, nombre o teléfono..." value="<?php echo isset($_POST['busqueda']) ? $_POST['busqueda'] : ''; ?>">
                    <button type="submit" name="buscar" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Buscar
                    </button>
                </div>
            </form>
            
            <?php
            // Mostrar la información del cliente si se encontró
            if ($cliente) {
                $hoy = date('Y-m-d');
                $membresia_vigente = false;
                $estado_texto = "Sin membresía";
                $estado_clase = "";
                
                if ($cliente['fecha_vencimiento']) {
                    if ($cliente['fecha_vencimiento'] >= $hoy) {
                        $membresia_vigente = true;
                        $estado_texto = "Vigente";
                        $estado_clase = "vigente";
                    } else {
                        $estado_texto = "Vencida";
                        $estado_clase = "vencida";
                    }
                }
                
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
                
                if ($cliente['direccion']) {
                    echo '<p><i class="fas fa-map-marker-alt me-2"></i>' . $cliente['direccion'] . '</p>';
                }
                
                if ($cliente['fecha_vencimiento']) {
                    echo '<p><i class="fas fa-calendar-alt me-2"></i>Vence: ' . date('d/m/Y', strtotime($cliente['fecha_vencimiento'])) . '</p>';
                }
                
                echo '</div>
                            </div>';
                
                echo '<div class="col-md-6">
                        <h4>Renovar Membresía</h4>
                        <form method="post" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">
                            <input type="hidden" name="cliente_id" value="' . $cliente['cliente_id'] . '">
                            <div class="mb-3">
                                <label for="tipo_membresia_renovacion" class="form-label">Nuevo Plan</label>
                                <select class="form-select" id="tipo_membresia_renovacion" name="tipo_membresia_renovacion" required>';
                
                mysqli_data_seek($resultado_membresias, 0);
                while ($membresia = mysqli_fetch_assoc($resultado_membresias)) {
                    $selected = ($cliente['tipo_membresia_id'] == $membresia['tipo_membresia_id']) ? 'selected' : '';
                    $precio_texto = number_format($membresia['precio'], 2);
                    echo '<option value="' . $membresia['tipo_membresia_id'] . '" ' . $selected . '>' . 
                          $membresia['nombre'] . ' - $' . $precio_texto . '</option>';
                }
                
                echo '</select>
                            </div>
                            <div class="mb-3">
                                <label for="metodo_pago_renovacion" class="form-label">Método de Pago</label>
                                <select class="form-select" id="metodo_pago_renovacion" name="metodo_pago_renovacion" required>';
                
                mysqli_data_seek($resultado_pagos, 0);
                while ($metodo = mysqli_fetch_assoc($resultado_pagos)) {
                    echo '<option value="' . $metodo['metodo_pago_id'] . '">' . $metodo['nombre'] . '</option>';
                }
                
                echo '</select>
                            </div>
                            <button type="submit" name="renovar" class="btn btn-success">
                                <i class="fas fa-sync-alt me-2"></i>Renovar Membresía
                            </button>
                        </form>
                      </div>
                    </div>
                  </div>';
                
                $query_historial = "SELECT m.membresia_id, m.fecha_inicio, m.fecha_vencimiento, 
                                   tm.nombre AS tipo_membresia, m.monto_pagado, mp.nombre AS metodo_pago
                                   FROM membresias m
                                   JOIN tipos_membresia tm ON m.tipo_membresia_id = tm.tipo_membresia_id
                                   JOIN metodos_pago mp ON m.metodo_pago_id = mp.metodo_pago_id
                                   WHERE m.cliente_id = ?
                                   ORDER BY m.fecha_vencimiento DESC";
                $stmt_historial = mysqli_prepare($conexion, $query_historial);
                mysqli_stmt_bind_param($stmt_historial, "i", $cliente['cliente_id']);
                mysqli_stmt_execute($stmt_historial);
                $resultado_historial = mysqli_stmt_get_result($stmt_historial);
                
                if (mysqli_num_rows($resultado_historial) > 0) {
                    echo '<h4 class="mt-4">Historial de Membresías</h4>
                          <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tipo</th>
                                        <th>Inicio</th>
                                        <th>Vencimiento</th>
                                        <th>Monto</th>
                                        <th>Método de Pago</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>';
                    
                    while ($historial = mysqli_fetch_assoc($resultado_historial)) {
                        $estado = $historial['fecha_vencimiento'] >= $hoy ? '<span class="badge bg-success">Vigente</span>' : '<span class="badge bg-danger">Vencida</span>';
                        echo '<tr>
                                <td>' . $historial['membresia_id'] . '</td>
                                <td>' . $historial['tipo_membresia'] . '</td>
                                <td>' . date('d/m/Y', strtotime($historial['fecha_inicio'])) . '</td>
<td>' . date('d/m/Y', strtotime($historial['fecha_vencimiento'])) . '</td>
                                <td>$' . number_format($historial['monto_pagado'], 2) . '</td>
                                <td>' . $historial['metodo_pago'] . '</td>
                                <td>' . $estado . '</td>
                              </tr>';
                    }
                    
                    echo '</tbody>
                        </table>
                      </div>';
                }
                
                $query_asistencias = "SELECT fecha_hora
                                    FROM asistencias
                                    WHERE cliente_id = ?
                                    ORDER BY fecha_hora DESC
                                    LIMIT 10";
                $stmt_asistencias = mysqli_prepare($conexion, $query_asistencias);
                mysqli_stmt_bind_param($stmt_asistencias, "i", $cliente['cliente_id']);
                mysqli_stmt_execute($stmt_asistencias);
                $resultado_asistencias = mysqli_stmt_get_result($stmt_asistencias);
                
                if (mysqli_num_rows($resultado_asistencias) > 0) {
                    echo '<h4 class="mt-4">Historial de Asistencias</h4>
                          <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                    </tr>
                                </thead>
                                <tbody>';
                    
                    while ($asistencia = mysqli_fetch_assoc($resultado_asistencias)) {
                        echo '<tr>
                                <td>' . date('d/m/Y', strtotime($asistencia['fecha_hora'])) . '</td>
                                <td>' . date('h:i A', strtotime($asistencia['fecha_hora'])) . '</td>
                              </tr>';
                    }
                    
                    echo '</tbody>
                        </table>
                      </div>';
                }
            }
            ?>
        </div>
        
        <div class="card animate__animated animate__fadeIn">
            <i class="fas fa-cat cat-paw"></i>
            <h2>
                <i class="fas fa-users gym-icon"></i>Lista de Clientes Activos
            </h2>
            
            <?php

            $query_activos = "SELECT 
                                c.cliente_id,
                                CONCAT(c.nombre, ' ', c.apellido) AS nombre_completo,
                                c.telefono,
                                tm.nombre AS tipo_membresia,
                                m.fecha_vencimiento,
                                DATEDIFF(m.fecha_vencimiento, CURDATE()) AS dias_restantes
                              FROM 
                                clientes c
                              JOIN 
                                membresias m ON c.cliente_id = m.cliente_id
                              JOIN 
                                tipos_membresia tm ON m.tipo_membresia_id = tm.tipo_membresia_id
                              WHERE 
                                m.fecha_vencimiento >= CURDATE()
                                AND c.activo = 1
                              ORDER BY 
                                dias_restantes ASC
                              LIMIT 20";
            
            $resultado_activos = mysqli_query($conexion, $query_activos);
            
            if ($resultado_activos && mysqli_num_rows($resultado_activos) > 0) {
                echo '<div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Teléfono</th>
                                    <th>Membresía</th>
                                    <th>Vencimiento</th>
                                    <th>Días restantes</th>
                                </tr>
                            </thead>
                            <tbody>';
                
                while ($cliente_activo = mysqli_fetch_assoc($resultado_activos)) {
                    // Determinar clase según días restantes
                    $clase_dias = '';
                    if ($cliente_activo['dias_restantes'] <= 7) {
                        $clase_dias = 'text-danger';
                    } else if ($cliente_activo['dias_restantes'] <= 15) {
                        $clase_dias = 'text-warning';
                    }
                    
                    echo '<tr>
                            <td>' . $cliente_activo['cliente_id'] . '</td>
                            <td>' . $cliente_activo['nombre_completo'] . '</td>
                            <td>' . $cliente_activo['telefono'] . '</td>
                            <td>' . $cliente_activo['tipo_membresia'] . '</td>
                            <td>' . date('d/m/Y', strtotime($cliente_activo['fecha_vencimiento'])) . '</td>
                            <td class="' . $clase_dias . '">' . $cliente_activo['dias_restantes'] . ' días</td>
                          </tr>';
                }
                
                echo '</tbody>
                    </table>
                  </div>';
            } else {
                echo '<div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No hay clientes con membresías activas.
                      </div>';
            }
            ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
