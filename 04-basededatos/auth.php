<?php
// Funciones compartidas: sesión, roles, registro y login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once "conexion.php";

// Página a la que va cada rol después de iniciar sesión
function panelDeRol($rol) {
    $paneles = [
        "alumno"  => "dashboard.php",
        "maestro" => "maestro.php",
        "admin"   => "admin.php",
    ];
    return $paneles[$rol];
}

// Login que corresponde a cada rol
function loginDeRol($rol) {
    return $rol === "alumno" ? "login.php" : "login_docente.php";
}

// Vacía la sesión y le asigna un id nuevo. La sesión sigue abierta
// (vacía) para que guardarAviso() pueda dejar un mensaje después.
function cerrarSesion() {
    $_SESSION = [];
    session_regenerate_id(true);
}

// Única fuente de verdad sobre quién está conectado: TODAS las páginas
// (y el menú) preguntan aquí en vez de leer $_SESSION directamente.
// Valida contra la BD; si la cuenta ya no existe, ya no está activa o el
// número ahora pertenece a otra persona (otro email), cierra la sesión.
// Devuelve el usuario (id, nombre, email, rol) o null.
function usuarioEnSesion() {
    global $conexion;
    static $usuario = false; // se consulta una sola vez por página

    if ($usuario !== false) {
        return $usuario;
    }
    $usuario = null;

    if (!isset($_SESSION["usuario_id"])) {
        return null;
    }

    $sql = "SELECT id, nombre, email, rol, estado FROM usuarios WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $_SESSION["usuario_id"]);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();

    if (!$fila || $fila["estado"] !== "activo" || $fila["email"] !== ($_SESSION["email"] ?? null)) {
        cerrarSesion();
        guardarAviso("error", "Tu sesión ya no es válida. Inicia sesión de nuevo.");
        return null;
    }

    // El rol y el nombre siempre salen de la BD: si el admin cambia algo, aplica al momento
    $_SESSION["rol"] = $fila["rol"];
    $_SESSION["nombre"] = $fila["nombre"];
    $usuario = $fila;
    return $usuario;
}

// Va al inicio de cada página protegida, por ejemplo: requiereRol(["admin"]);
function requiereRol($rolesPermitidos) {
    // Que el navegador no guarde copia: tras cerrar sesión, "Atrás" no muestra el panel
    header("Cache-Control: no-store");

    $usuario = usuarioEnSesion();

    if (!$usuario) {
        $login = in_array("alumno", $rolesPermitidos) ? "login.php" : "login_docente.php";
        header("Location: " . $login);
        exit;
    }

    // Tiene sesión pero no permiso para esta página: lo mandamos a su panel
    if (!in_array($usuario["rol"], $rolesPermitidos)) {
        header("Location: " . panelDeRol($usuario["rol"]));
        exit;
    }
}

// Token contra CSRF: impide que otra página envíe formularios a nombre
// del usuario. Va dentro de cada <form> que modifica datos.
function campoCsrf() {
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf" value="' . $_SESSION["csrf"] . '">';
}

function verificarCsrf() {
    if (!hash_equals($_SESSION["csrf"] ?? "", $_POST["csrf"] ?? "")) {
        http_response_code(400);
        die("Solicitud no válida. Recarga la página e intenta de nuevo.");
    }
}

// Mensaje que sobrevive a una redirección; header.php lo muestra una sola vez
function guardarAviso($tipo, $mensaje) {
    $_SESSION["aviso"] = ["tipo" => $tipo, "mensaje" => $mensaje];
}

// Nombre para mostrar de cada rol
function nombreRol($rol) {
    $nombres = ["alumno" => "Alumno", "maestro" => "Docente", "admin" => "Administrador"];
    return $nombres[$rol];
}

function obtenerCarreras() {
    global $conexion;
    return $conexion->query("SELECT id, nombre FROM carreras ORDER BY nombre");
}

// Registra un usuario como "pendiente". El rol queda como solicitud:
// no da ningún permiso hasta que el admin apruebe la cuenta.
// Devuelve ["tipo" => "exito"|"error", "mensaje" => ..., "enlace" => ...]
function registrarUsuario($id, $nombre, $email, $password, $rol, $carreraId) {
    global $conexion;

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));

    $sql = "INSERT INTO usuarios
            (id, nombre, email, password, rol, carrera_id, token_verification)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(
        "issssis",
        $id,
        $nombre,
        $email,
        $passwordHash,
        $rol,
        $carreraId,
        $token
    );

    try {
        $stmt->execute();
        return [
            "tipo"    => "exito",
            "mensaje" => "Registro recibido. Confirma tu email y espera a que el administrador apruebe tu cuenta.",
            // Simula el correo de confirmación: el enlace se muestra en la página
            "enlace"  => "verificar.php?token=" . $token,
        ];
    } catch (mysqli_sql_exception $e) {
        // 1062 = entrada duplicada (id o email ya existen)
        // 1452 = la carrera elegida no existe
        if ($e->getCode() === 1062) {
            $mensaje = "Ese número o email ya está registrado.";
        } elseif ($e->getCode() === 1452) {
            $mensaje = "Selecciona una carrera válida.";
        } else {
            $mensaje = "No se pudo completar el registro.";
        }
        return ["tipo" => "error", "mensaje" => $mensaje, "enlace" => ""];
    }
}

// Valida credenciales y que la cuenta pertenezca a este acceso.
// Si todo está bien inicia sesión y redirige; si no, devuelve el mensaje de error.
function iniciarSesion($email, $password, $rolesPermitidos) {
    global $conexion;

    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows !== 1) {
        return "Usuario no encontrado.";
    }

    $usuario = $resultado->fetch_assoc();

    // La contraseña va primero: sin ella no revelamos nada del estado de la cuenta
    if (!password_verify($password, $usuario["password"])) {
        return "Contraseña incorrecta.";
    }
    if (!in_array($usuario["rol"], $rolesPermitidos)) {
        return $usuario["rol"] === "alumno"
            ? "Esta cuenta es de alumno. Usa el acceso de alumnos."
            : "Esta cuenta es de docente. Usa el acceso de docentes.";
    }
    if (!$usuario["email_verificado"]) {
        return "Primero confirma tu email.";
    }
    if ($usuario["estado"] === "pendiente") {
        return "Tu cuenta está pendiente de aprobación por el administrador.";
    }
    if ($usuario["estado"] === "rechazado") {
        return "Tu solicitud de registro fue rechazada.";
    }

    // Empieza una sesión limpia: no se arrastra nada de una cuenta anterior
    cerrarSesion();
    $_SESSION["usuario_id"] = $usuario["id"];
    $_SESSION["email"] = $usuario["email"];
    $_SESSION["nombre"] = $usuario["nombre"];
    $_SESSION["rol"] = $usuario["rol"];
    header("Location: " . panelDeRol($usuario["rol"]));
    exit;
}
