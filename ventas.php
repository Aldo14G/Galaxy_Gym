<?php
// Iniciar sesión para el carrito
session_start();

// Incluir archivo de conexión
require_once 'conexion.php';

// Inicializar el carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = array();
    $_SESSION['total'] = 0;
}

// Variables para mensajes
$mensaje = "";
$tipo_mensaje = "";

// Agregar producto al carrito
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar_producto'])) {
    $id_producto = $_POST['id_producto'];
    $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;
    
    if ($cantidad <= 0) {
        $mensaje = "La cantidad debe ser mayor a cero.";
        $tipo_mensaje = "error";
    } else {
        if ($conexion) {
            // Obtener información del producto
            $sql_producto = "SELECT nombre, precio, stock FROM productos WHERE producto_id = ?";
            $stmt_producto = mysqli_prepare($conexion, $sql_producto);
            mysqli_stmt_bind_param($stmt_producto, "i", $id_producto);
            mysqli_stmt_execute($stmt_producto);
            $resultado_producto = mysqli_stmt_get_result($stmt_producto);
            
            if ($producto = mysqli_fetch_assoc($resultado_producto)) {
                // Verificar si hay suficiente stock
                if ($producto['stock'] >= $cantidad) {
                    // Verificar si ya existe en el carrito
                    if (isset($_SESSION['carrito'][$id_producto])) {
                        // Actualizar cantidad
                        $nueva_cantidad = $_SESSION['carrito'][$id_producto]['cantidad'] + $cantidad;
                        
                        if ($nueva_cantidad <= $producto['stock']) {
                            $_SESSION['carrito'][$id_producto]['cantidad'] = $nueva_cantidad;
                            $_SESSION['carrito'][$id_producto]['subtotal'] = $nueva_cantidad * $producto['precio'];
                            $mensaje = "Se actualizó la cantidad de " . $producto['nombre'] . " en el carrito.";
                            $tipo_mensaje = "success";
                        } else {
                            $mensaje = "No hay suficiente stock disponible para " . $producto['nombre'] . ".";
                            $tipo_mensaje = "error";
                        }
                    } else {
                        // Añadir nuevo producto al carrito
                        $_SESSION['carrito'][$id_producto] = array(
                            'nombre' => $producto['nombre'],
                            'precio' => $producto['precio'],
                            'cantidad' => $cantidad,
                            'subtotal' => $cantidad * $producto['precio']
                        );
                        $mensaje = $producto['nombre'] . " agregado al carrito.";
                        $tipo_mensaje = "success";
                    }
                    
                    // Actualizar total
                    $_SESSION['total'] = calcular_total($_SESSION['carrito']);
                } else {
                    $mensaje = "No hay suficiente stock disponible para " . $producto['nombre'] . ".";
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "Producto no encontrado.";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Error en la conexión a la base de datos: " . mysqli_connect_error();
            $tipo_mensaje = "error";
        }
    }
}

// Quitar producto del carrito
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['quitar_producto'])) {
    $id_producto = $_POST['id_producto'];
    
    if (isset($_SESSION['carrito'][$id_producto])) {
        $nombre_producto = $_SESSION['carrito'][$id_producto]['nombre'];
        unset($_SESSION['carrito'][$id_producto]);
        $_SESSION['total'] = calcular_total($_SESSION['carrito']);
        $mensaje = $nombre_producto . " eliminado del carrito.";
        $tipo_mensaje = "success";
    }
}

// Vaciar carrito
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['vaciar_carrito'])) {
    $_SESSION['carrito'] = array();
    $_SESSION['total'] = 0;
    $mensaje = "El carrito ha sido vaciado.";
    $tipo_mensaje = "success";
}

// Finalizar venta
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['finalizar_venta'])) {
    $cliente_id = !empty($_POST['cliente_id']) ? $_POST['cliente_id'] : null;
    $metodo_pago_id = $_POST['metodo_pago_id'];
    $total_venta = $_SESSION['total'];
    
    if (empty($_SESSION['carrito'])) {
        $mensaje = "No hay productos en el carrito para realizar la venta.";
        $tipo_mensaje = "error";
    } else {
        if ($conexion) {
            mysqli_begin_transaction($conexion);
            
            try {
                // Registrar venta
                $sql_venta = "INSERT INTO ventas (cliente_id, fecha, total, metodo_pago_id) VALUES (?, NOW(), ?, ?)";
                $stmt_venta = mysqli_prepare($conexion, $sql_venta);
                mysqli_stmt_bind_param($stmt_venta, "idi", $cliente_id, $total_venta, $metodo_pago_id);
                mysqli_stmt_execute($stmt_venta);
                
                $id_venta = mysqli_insert_id($conexion);
                
                // Registrar detalle de venta y actualizar stock
                foreach ($_SESSION['carrito'] as $id_producto => $producto) {
                    $sql_detalle = "INSERT INTO detalles_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)";
                    $stmt_detalle = mysqli_prepare($conexion, $sql_detalle);
                    mysqli_stmt_bind_param($stmt_detalle, "iiddd", $id_venta, $id_producto, $producto['cantidad'], $producto['precio'], $producto['subtotal']);
                    mysqli_stmt_execute($stmt_detalle);
                    
                    // Actualizar stock
                    $sql_stock = "UPDATE productos SET stock = stock - ? WHERE producto_id = ?";
                    $stmt_stock = mysqli_prepare($conexion, $sql_stock);
                    mysqli_stmt_bind_param($stmt_stock, "di", $producto['cantidad'], $id_producto);
                    mysqli_stmt_execute($stmt_stock);
                }
                
                mysqli_commit($conexion);
                
                // Limpiar el carrito
                $_SESSION['carrito'] = array();
                $_SESSION['total'] = 0;
                
                $mensaje = "¡Venta registrada exitosamente! Folio: " . $id_venta;
                $tipo_mensaje = "success";
            } catch (Exception $e) {
                mysqli_rollback($conexion);
                $mensaje = "Error al procesar la venta: " . $e->getMessage();
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "Error en la conexión a la base de datos: " . mysqli_connect_error();
            $tipo_mensaje = "error";
        }
    }
}

// Función para calcular el total del carrito
function calcular_total($carrito) {
    $total = 0;
    foreach ($carrito as $producto) {
        $total += $producto['subtotal'];
    }
    return $total;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venta de Productos - Galaxy Gym</title>
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
        
        .btn-outline-danger {
            color: #e74c3c;
            border-color: #e74c3c;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-outline-danger:hover {
            background-color: #e74c3c;
            color: white;
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
        
        .producto-item {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        
        .cat-icon-small {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 0.8rem;
            color: var(--gray-color);
            opacity: 0.3;
        }
        
        .producto-info {
            flex-grow: 1;
        }
        
        .producto-nombre {
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 0.25rem;
        }
        
        .producto-categoria {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .producto-precio {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .producto-stock {
            background-color: rgba(0, 102, 204, 0.1);
            color: var(--primary-color);
            padding: 0.3rem 0.6rem;
            border-radius: 50rem;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .stock-bajo {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
        
        .carrito-item {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        
        .carrito-total {
            background-color: rgba(0, 102, 204, 0.1);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            text-align: right;
        }
        
        .total-texto {
            font-size: 1.2rem;
            color: var(--accent-color);
            margin-bottom: 0.5rem;
        }
        
        .total-precio {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
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
                <i class="fas fa-shopping-cart gym-icon"></i>Venta de Productos
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
                <!-- Lista de productos -->
                <div class="col-md-7">
                    <h4 class="mb-3">Productos disponibles</h4>
                    
                    <?php
                    if ($conexion) {
                        // Obtener categorías para filtrar
                        $sql_categorias = "SELECT categoria_id, nombre FROM categorias_producto ORDER BY nombre";
                        $result_categorias = mysqli_query($conexion, $sql_categorias);
                        
                        // Filtro de categorías
                        echo '<div class="mb-3">
                                <form method="get" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" class="d-flex">
                                    <select name="categoria" class="form-select me-2">
                                        <option value="">Todas las categorías</option>';
                        
                        while ($categoria = mysqli_fetch_assoc($result_categorias)) {
                            $selected = (isset($_GET['categoria']) && $_GET['categoria'] == $categoria['categoria_id']) ? 'selected' : '';
                            echo '<option value="' . $categoria['categoria_id'] . '" ' . $selected . '>' . $categoria['nombre'] . '</option>';
                        }
                        
                        echo '</select>
                                    <button type="submit" class="btn btn-primary">Filtrar</button>
                                </form>
                              </div>';
                        
                        // Consulta de productos con filtro si existe
                        $filtro_categoria = isset($_GET['categoria']) && !empty($_GET['categoria']) ? $_GET['categoria'] : null;
                        
                        if ($filtro_categoria) {
                            $sql_productos = "SELECT p.producto_id, p.nombre, p.precio, p.stock, cp.nombre AS categoria 
                                           FROM productos p
                                           JOIN categorias_producto cp ON p.categoria_id = cp.categoria_id
                                           WHERE p.categoria_id = ?
                                           ORDER BY p.nombre";
                            $stmt_productos = mysqli_prepare($conexion, $sql_productos);
                            mysqli_stmt_bind_param($stmt_productos, "i", $filtro_categoria);
                        } else {
                            $sql_productos = "SELECT p.producto_id, p.nombre, p.precio, p.stock, cp.nombre AS categoria 
                                           FROM productos p
                                           JOIN categorias_producto cp ON p.categoria_id = cp.categoria_id
                                           ORDER BY p.nombre";
                            $stmt_productos = mysqli_prepare($conexion, $sql_productos);
                        }
                        
                        mysqli_stmt_execute($stmt_productos);
                        $result_productos = mysqli_stmt_get_result($stmt_productos);
                        
                        if (mysqli_num_rows($result_productos) > 0) {
                            while ($producto = mysqli_fetch_assoc($result_productos)) {
                                // Verificar si hay stock bajo
                                $stock_clase = ($producto['stock'] < 5) ? 'stock-bajo' : '';
                                $es_proteina_preparada = (strpos(strtolower($producto['categoria']), 'proteína') !== false && strpos(strtolower($producto['nombre']), 'prepara') !== false);
                                
                                echo '<div class="producto-item">
                                        <i class="fas fa-paw cat-icon-small"></i>
                                        <div class="producto-info">
                                            <div class="producto-nombre">' . $producto['nombre'] . '</div>
                                            <div class="producto-categoria">' . $producto['categoria'] . '</div>
                                        </div>
                                        <div class="text-end me-3">
                                            <div class="producto-precio">$' . number_format($producto['precio'], 2) . '</div>
                                            <span class="producto-stock ' . $stock_clase . '">Stock: ' . $producto['stock'] . '</span>
                                        </div>
                                        <form method="post" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" class="d-flex align-items-center">
                                            <input type="hidden" name="id_producto" value="' . $producto['producto_id'] . '">';
                                
                                echo '<input type="number" name="cantidad" value="1" min="1" max="' . $producto['stock'] . '" class="form-control form-control-sm me-2" style="width: 70px;">';
                                
                                echo '<button type="submit" name="agregar_producto" class="btn btn-sm btn-primary" ' . ($producto['stock'] > 0 ? '' : 'disabled') . '>
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        </form>
                                      </div>';
                            }
                        } else {
                            echo '<div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No hay productos disponibles en esta categoría.
                                  </div>';
                        }
                    }
                    ?>
                </div>
                
                <!-- Carrito de compras -->
                <div class="col-md-5">
                    <h4 class="mb-3">Carrito de compra</h4>
                    
                    <?php
                    if (empty($_SESSION['carrito'])) {
                        echo '<div class="alert alert-info">
                                <i class="fas fa-shopping-cart me-2"></i>
                                El carrito está vacío.
                              </div>';
                    } else {
                        foreach ($_SESSION['carrito'] as $id_producto => $producto) {
                            echo '<div class="carrito-item">
                                    <div>
                                        <div class="fw-bold">' . $producto['nombre'] . '</div>
                                        <div class="small text-muted">
                                            ' . $producto['cantidad'] . ' x $' . number_format($producto['precio'], 2) . '
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3 fw-bold">$' . number_format($producto['subtotal'], 2) . '</div>
                                        <form method="post" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">
                                            <input type="hidden" name="id_producto" value="' . $id_producto . '">
                                            <button type="submit" name="quitar_producto" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                  </div>';
                        }
                        
                        echo '<div class="carrito-total">
                                <div class="total-texto">Total:</div>
                                <div class="total-precio">$' . number_format($_SESSION['total'], 2) . '</div>
                              </div>';
                        
                        // Formulario para finalizar venta
                        echo '<form method="post" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" class="mt-4">
                                <div class="mb-3">
                                    <label for="cliente_id" class="form-label">ID Cliente (opcional)</label>
                                    <input type="text" class="form-control" id="cliente_id" name="cliente_id" placeholder="Ingrese ID del cliente">
                                    <div class="form-text">Deje en blanco para venta sin cliente asignado.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="metodo_pago_id" class="form-label">Método de Pago</label>
                                    <select class="form-select" id="metodo_pago_id" name="metodo_pago_id" required>';
                        
                        // Obtener métodos de pago desde la base de datos
                        $query_pagos = "SELECT metodo_pago_id, nombre FROM metodos_pago ORDER BY nombre";
                        $resultado_pagos = mysqli_query($conexion, $query_pagos);
                        
                        while ($metodo = mysqli_fetch_assoc($resultado_pagos)) {
                            echo '<option value="' . $metodo['metodo_pago_id'] . '">' . $metodo['nombre'] . '</option>';
                        }
                        
                        echo '</select>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <button type="submit" name="vaciar_carrito" class="btn btn-outline-secondary">
                                        <i class="fas fa-trash-alt me-2"></i>Vaciar carrito
                                    </button>
                                    <button type="submit" name="finalizar_venta" class="btn btn-success">
                                        <i class="fas fa-check me-2"></i>Finalizar venta
                                    </button>
                                </div>
                              </form>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="card animate__animated animate__fadeIn">
            <i class="fas fa-paw cat-paw"></i>
            <h2>
                <i class="fas fa-receipt gym-icon"></i>Ventas recientes
            </h2>
            
            <?php
            if ($conexion) {
                $sql_ventas = "SELECT v.venta_id, v.fecha, 
                             IFNULL(CONCAT(c.nombre, ' ', c.apellido), 'Sin cliente') AS cliente, 
                             v.total, mp.nombre AS metodo_pago
                             FROM ventas v
                             LEFT JOIN clientes c ON v.cliente_id = c.cliente_id
                             LEFT JOIN metodos_pago mp ON v.metodo_pago_id = mp.metodo_pago_id
                             ORDER BY v.fecha DESC LIMIT 10";
                
                $result_ventas = mysqli_query($conexion, $sql_ventas);
                
                if ($result_ventas && mysqli_num_rows($result_ventas) > 0) {
                    echo '<div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Folio</th>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>Total</th>
                                        <th>Método de pago</th>
                                        <th>Detalles</th>
                                    </tr>
                                </thead>
                                <tbody>';
                    
                    while ($venta = mysqli_fetch_assoc($result_ventas)) {
                        echo '<tr>
                                <td>' . $venta['venta_id'] . '</td>
                                <td>' . date('d/m/Y H:i', strtotime($venta['fecha'])) . '</td>
                                <td>' . $venta['cliente'] . '</td>
<td>$' . number_format($venta['total'], 2) . '</td>
                                <td>' . $venta['metodo_pago'] . '</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary ver-detalles" data-id="' . $venta['venta_id'] . '">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                              </tr>';
                    }
                    
                    echo '</tbody>
                        </table>
                      </div>';
                } else {
                    echo '<div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No hay ventas registradas.
                          </div>';
                }
            }
            ?>
        </div>
        
        <!-- Modal para detalles de venta -->
        <div class="modal fade" id="detallesVentaModal" tabindex="-1" aria-labelledby="detallesVentaModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detallesVentaModalLabel">Detalles de Venta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3" id="ventaInfo">
                            <!-- Aquí se mostrará la información de la venta -->
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="detallesTable">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unitario</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Aquí se mostrarán los detalles de la venta -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para cargar detalles de venta
        document.addEventListener('DOMContentLoaded', function() {
            const botonesDetalles = document.querySelectorAll('.ver-detalles');
            
            botonesDetalles.forEach(boton => {
                boton.addEventListener('click', function() {
                    const ventaId = this.getAttribute('data-id');
                    
                    // Hacer una consulta AJAX para obtener detalles
                    fetch('obtener_detalles_venta.php?id=' + ventaId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                alert('Error: ' + data.error);
                                return;
                            }
                            
                            // Mostrar información de la venta
                            const ventaInfo = document.getElementById('ventaInfo');
                            ventaInfo.innerHTML = `
                                <p><strong>Folio:</strong> ${data.venta.id_venta}</p>
                                <p><strong>Fecha:</strong> ${data.venta.fecha_venta}</p>
                                <p><strong>Cliente:</strong> ${data.venta.cliente}</p>
                                <p><strong>Método de Pago:</strong> ${data.venta.metodo_pago}</p>
                                <p><strong>Total:</strong> $${parseFloat(data.venta.total).toFixed(2)}</p>
                            `;
                            
                            // Mostrar detalles de la venta
                            const detallesTable = document.getElementById('detallesTable').getElementsByTagName('tbody')[0];
                            detallesTable.innerHTML = '';
                            
                            data.detalles.forEach(detalle => {
                                const row = detallesTable.insertRow();
                                row.innerHTML = `
                                    <td>${detalle.nombre}</td>
                                    <td>${detalle.cantidad}</td>
                                    <td>$${parseFloat(detalle.precio_unitario).toFixed(2)}</td>
                                    <td>$${parseFloat(detalle.subtotal).toFixed(2)}</td>
                                `;
                            });
                            
                            // Mostrar el modal
                            const modal = new bootstrap.Modal(document.getElementById('detallesVentaModal'));
                            modal.show();
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error al obtener los detalles de la venta');
                        });
                });
            });
        });
    </script>
</body>
</html>