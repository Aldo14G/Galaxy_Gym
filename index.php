<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión - Galaxy Gym</title>
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
        
        .main-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            margin-bottom: 2rem;
            border: none;
        }
        
        h1 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
        }
        
        h1:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 2px;
        }
        
        .module-card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .module-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary-color);
        }
        
        .module-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .card-body {
            padding: 2rem 1.5rem;
        }
        
        .module-icon {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .clientes-icon {
            color: #4e73df;
        }
        
        .accesos-icon {
            color: #36b9cc;
        }
        
        .ventas-icon {
            color: #1cc88a;
        }
        
        .cat-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.2rem;
            color: var(--gray-color);
            opacity: 0.5;
            transition: all 0.3s ease;
        }
        
        .module-card:hover .cat-icon {
            transform: scale(1.2);
            opacity: 1;
            color: var(--primary-color);
        }
        
        .module-card:hover .module-icon {
            transform: scale(1.2);
        }
        
        .module-card h3 {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }
        
        .module-card p {
            color: var(--gray-color);
            margin-bottom: 1.5rem;
        }
        
        .btn-module {
            border-radius: 50px;
            padding: 0.5rem 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-clientes {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        
        .btn-clientes:hover {
            background-color: #2e59d9;
            border-color: #2e59d9;
            transform: translateY(-2px);
        }
        
        .btn-accesos {
            background-color: #36b9cc;
            border-color: #36b9cc;
        }
        
        .btn-accesos:hover {
            background-color: #2c9faf;
            border-color: #2c9faf;
            transform: translateY(-2px);
        }
        
        .btn-ventas {
            background-color: #1cc88a;
            border-color: #1cc88a;
        }
        
        .btn-ventas:hover {
            background-color: #169b6b;
            border-color: #169b6b;
            transform: translateY(-2px);
        }
        
        .footer {
            color: rgba(255, 255, 255, 0.7);
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .logo-cat {
            font-size: 1.5rem;
            margin-left: 5px;
            transform: rotate(10deg);
            display: inline-block;
            color: white;
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
        
        .cat-paw {
            position: absolute;
            bottom: 10px;
            right: 10px;
            font-size: 3rem;
            color: rgba(0, 102, 204, 0.1);
            transform: rotate(15deg);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-card animate__animated animate__fadeIn text-center">
            <h1>
                <i class="fas fa-dumbbell me-2"></i>GALAXY GYM
                <i class="fas fa-cat logo-cat"></i>
            </h1>
            
            <div class="row mt-5">
                <div class="col-md-4 mb-4">
                    <div class="module-card">
                        <i class="fas fa-cat cat-icon"></i>
                        <div class="card-body text-center">
                            <i class="fas fa-user-plus fa-4x module-icon clientes-icon"></i>
                            <h3>Clientes</h3>
                            <p>Registro de clientes y gestión de membresías</p>
                            <a href="clientes.php" class="btn btn-primary btn-module btn-clientes">
                                <i class="fas fa-arrow-right me-2"></i>Acceder
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="module-card">
                        <i class="fas fa-paw cat-icon"></i>
                        <div class="card-body text-center">
                            <i class="fas fa-id-card fa-4x module-icon accesos-icon"></i>
                            <h3>Accesos</h3>
                            <p>Control de entradas y salidas de clientes</p>
                            <a href="accesos.php" class="btn btn-info btn-module btn-accesos">
                                <i class="fas fa-arrow-right me-2"></i>Acceder
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="module-card">
                        <i class="fas fa-paw cat-icon"></i>
                        <div class="card-body text-center">
                            <i class="fas fa-shopping-cart fa-4x module-icon ventas-icon"></i>
                            <h3>Ventas</h3>
                            <p>Gestión de productos y registro de ventas</p>
                            <a href="ventas.php" class="btn btn-success btn-module btn-ventas">
                                <i class="fas fa-arrow-right me-2"></i>Acceder
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card animate__animated animate__fadeIn mt-4">
            <i class="fas fa-paw cat-paw"></i>
            <div class="card-body">
                <h4 class="mb-3"><i class="fas fa-clock me-2"></i>Horarios de atención</h4>
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Lunes a Viernes:</strong><br>5:00 AM - 10:00 PM</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Sábados:</strong><br>5:00 AM - 2:00 PM</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Domingos:</strong><br>8:00 AM - 12:00 PM</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer animate__animated animate__fadeIn animate__delay-1s">
            <p>Base de Datos creada por: Aldo G. M. Pineda &copy; <?php echo date("Y"); ?></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
