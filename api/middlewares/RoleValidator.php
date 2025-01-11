<?php

namespace api\middlewares;

class RoleValidator
{
    public static function validate($currentUserRoleId, $targetRoleId)
    {
        switch ($currentUserRoleId) {
            case 1: // Superadministrador
                if (!in_array($targetRoleId, [2, 3])) {
                    throw new \Exception('Permiso denegado para asignar este rol');
                }
                break;

            case 2: // Administrador
                if ($targetRoleId !== 3) {
                    throw new \Exception('Permiso denegado para asignar este rol');
                }
                break;

            case 3: // Usuario estándar
                throw new \Exception('Permiso denegado para registrar usuarios');

            default:
                throw new \Exception('Rol no válido');
        }
    }

    public static function validateUpdatePermission($currentUser, $targetUser, $canUpdateOwnProfile = false)
    {

        switch ($currentUser['role_id']) {
            case 1: // Superadministrador
                // Puede modificar a cualquier usuario
                return;

            case 2: // Administrador
                // Puede modificar su propio perfil o usuarios Rol 3 que ha registrado
                if ($currentUser['id'] !== $targetUser['id'] && $targetUser['role_id'] !== 3) {
                    throw new \Exception('No tienes permisos para modificar este usuario');
                }

                if ($targetUser['role_id'] === 3 && $targetUser['admin_id'] !== $currentUser['id']) {
                    throw new \Exception('No tienes permisos para modificar este usuario');
                }
                break;

            case 3: // Usuario estándar
                // Puede modificar su propio perfil solo si canUpdateOwnProfile es true y el usuario es el mismo
                if (!$canUpdateOwnProfile || $currentUser['id'] !== $targetUser['id']) {
                    throw new \Exception('No tienes permisos para modificar este usuario');
                }
                break;
            default:
                throw new \Exception('Rol no válido');
        }
    }

    public static function validateDeletePermission($currentUser, $targetUser)
    {
        switch ($currentUser['role_id']) {
            case 1: // Superadministrador
                // Puede eliminar cualquier usuario
                return;

            case 2: // Administrador
                // Puede eliminar solo usuarios con rol 3 que ha registrado
                if ($targetUser['role_id'] !== 3 || $targetUser['admin_id'] !== $currentUser['id']) {
                    throw new \Exception('No tienes permisos para eliminar este usuario');
                }
                break;

            case 3: // Usuario estándar
                // No puede eliminar a ningún usuario
                throw new \Exception('No tienes permisos para eliminar usuarios');

            default:
                throw new \Exception('Rol no válido');
        }
    }
}
