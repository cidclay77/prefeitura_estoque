<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function requireRole($required_role) {
    requireLogin();
    
    $role_hierarchy = [
        'visualizador' => 1,
        'operador' => 2,
        'administrador' => 3
    ];

    $user_level = $role_hierarchy[$_SESSION['user_role']] ?? 0;
    $required_level = $role_hierarchy[$required_role] ?? 0;

    if ($user_level < $required_level) {
        header("Location: access_denied.php");
        exit;
    }
}

function hasPermission($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $role_hierarchy = [
        'visualizador' => 1,
        'operador' => 2,
        'administrador' => 3
    ];

    $user_level = $role_hierarchy[$_SESSION['user_role']] ?? 0;
    $required_level = $role_hierarchy[$required_role] ?? 0;

    return $user_level >= $required_level;
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['user_role']
    ];
}

function logout() {
    session_destroy();
    header("Location: login.php");
    exit;
}

function getRoleName($role) {
    $roles = [
        'administrador' => 'Administrador',
        'operador' => 'Operador',
        'visualizador' => 'Visualizador'
    ];
    
    return $roles[$role] ?? 'Desconhecido';
}

function getRoleColor($role) {
    $colors = [
        'administrador' => 'bg-red-100 text-red-800',
        'operador' => 'bg-blue-100 text-blue-800',
        'visualizador' => 'bg-green-100 text-green-800'
    ];
    
    return $colors[$role] ?? 'bg-gray-100 text-gray-800';
}
?>