<?php
// Conexión a la base de datos
require_once 'conexion.php';

// Verificar que se recibió un ID de venta
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_venta = $_GET['id'];
    
    try {
        // Información general de la venta
        $sql_venta = "SELECT v.venta_id, v.fecha, v.total, mp.nombre AS metodo_pago, 
                       IFNULL(CONCAT(c.nombre, ' ', c.apellido), 'Sin cliente asignado') AS cliente
                     FROM ventas v
                     LEFT JOIN clientes c ON v.cliente_id = c.cliente_id
                     LEFT JOIN metodos_pago mp ON v.metodo_pago_id = mp.metodo_pago_id
                     WHERE v.venta_id = ?";
        
        $stmt_venta = mysqli_prepare($conexion, $sql_venta);
        mysqli_stmt_bind_param($stmt_venta, "i", $id_venta);
        mysqli_stmt_execute($stmt_venta);
        $resultado_venta = mysqli_stmt_get_result($stmt_venta);
        
        if ($venta = mysqli_fetch_assoc($resultado_venta)) {
            // Obtener los detalles de la venta
            $sql_detalles = "SELECT p.nombre, dv.cantidad, dv.precio_unitario, dv.subtotal
                           FROM detalles_venta dv
                           JOIN productos p ON dv.producto_id = p.producto_id
                           WHERE dv.venta_id = ?";
            
            $stmt_detalles = mysqli_prepare($conexion, $sql_detalles);
            mysqli_stmt_bind_param($stmt_detalles, "i", $id_venta);
            mysqli_stmt_execute($stmt_detalles);
            $resultado_detalles = mysqli_stmt_get_result($stmt_detalles);
            
            $detalles = array();
            while ($detalle = mysqli_fetch_assoc($resultado_detalles)) {
                $detalles[] = $detalle;
            }
            
            // Preparar respuesta
            $respuesta = array(
                'venta' => $venta,
                'detalles' => $detalles
            );
            
            // Devolver como JSON
            header('Content-Type: application/json');
            echo json_encode($respuesta);
            
        } else {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(array('error' => 'Venta no encontrada'));
        }
        
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(array('error' => $e->getMessage()));
    }
    
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(array('error' => 'ID de venta no válido'));
}

// Cerrar la conexión
mysqli_close($conexion);
?>