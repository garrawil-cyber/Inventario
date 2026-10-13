<?php
/**
 * ============================================================
 *  MODELO: Usuario.php
 *  Sistema : RopaVirtual – Módulo de Usuarios
 *  Arquitectura: MVC  |  Capa: Model
 *  Base de datos: ropavirtual_db
 * ============================================================
 *
 *  Stored Procedures utilizados:
 *   - sp_ListarUsuarios        (filtro, rol, estado, limit, offset)
 *   - sp_GetUsuarioById        (id_usuario → OUT p_encontrado)
 *   - sp_InsertUsuario         (OUT p_id_usuario, OUT p_mensaje)
 *   - sp_UpdateUsuario         (OUT p_mensaje)
 *   - sp_DeleteUsuario         (OUT p_eliminado, OUT p_mensaje)
 *   - sp_ChangePassword        (OUT p_cambiado, OUT p_mensaje)
 *   - sp_GetUsuariosForSelect  (p_rol)
 *
 *  Patrón de llamada a SPs con parámetros OUT:
 *    CALL sp_Nombre(val1, val2, @out1, @out2);
 *    SELECT @out1 AS alias1, @out2 AS alias2;
 * ============================================================
 */

require_once __DIR__ . '/../config/database.php';

class Usuario
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    // =========================================================================
    //  LISTAR con filtros y paginación
    //  SP: sp_ListarUsuarios(p_filtro, p_rol, p_estado, p_limit, p_offset)
    //  Devuelve 2 result-sets: filas + fila con total_registros
    // =========================================================================
    public function listar(array $filters = []): array
    {
        try {
            $filtro = $filters['filtro'] ?? null;
            $rol    = $filters['rol']    ?? null;
            $estado = $filters['estado'] ?? null;
            $limit  = (int)($filters['limit']  ?? 20);
            $offset = (int)($filters['offset'] ?? 0);

            $sql = "CALL sp_ListarUsuarios(:filtro, :rol, :estado, :limit, :offset)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':filtro',  $filtro ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':rol',    $rol    ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':estado', $estado ?: null, PDO::PARAM_STR);
            $stmt->bindValue(':limit',  $limit,          PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset,         PDO::PARAM_INT);
            $stmt->execute();

            // Primer result-set → filas de usuarios
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Segundo result-set → total_registros (SQL_CALC_FOUND_ROWS)
            $total = 0;
            if ($stmt->nextRowset()) {
                $row   = $stmt->fetch(PDO::FETCH_ASSOC);
                $total = (int)($row['total_registros'] ?? 0);
            }

            $stmt->closeCursor();

            return [
                'success' => true,
                'data'    => $data,
                'total'   => $total,
            ];

        } catch (\PDOException $e) {
            error_log("Usuario::listar — " . $e->getMessage());
            return ['success' => false, 'data' => [], 'total' => 0, 'mensaje' => 'Error al obtener usuarios'];
        }
    }

 /**
 * OBTENER POR ID
 * SP: sp_GetUsuarioById(p_id_usuario)
 */
public function getById(int $id): array
{
    try {
        $stmt = $this->db->prepare("CALL sp_GetUsuarioById(:id)");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        if ($usuario) {
            return [
                'success' => true, 
                'usuario' => $usuario
            ];
        }
        
        return [
            'success' => false, 
            'mensaje' => 'Usuario no encontrado'
        ];
        
    } catch (\PDOException $e) {
        error_log("Usuario::getById — " . $e->getMessage());
        return [
            'success' => false, 
            'mensaje' => 'Error al obtener el usuario'
        ];
    }
}

    // =========================================================================
    //  CREAR
    //  SP: sp_InsertUsuario(username, password, nombre_completo, email,
    //                       rol, estado, OUT p_id_usuario, OUT p_mensaje)
    //  La contraseña llega ya hasheada con password_hash() desde el controlador.
    // =========================================================================
    public function crear(array $data): array
    {
        try {
            $sql = "CALL sp_InsertUsuario(
                        :username, :password, :nombre_completo,
                        :email, :rol, :estado,
                        @p_id_usuario, @p_mensaje
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':username',        trim($data['username']),        PDO::PARAM_STR);
            $stmt->bindValue(':password',        $data['password'],              PDO::PARAM_STR);
            $stmt->bindValue(':nombre_completo', trim($data['nombre_completo']), PDO::PARAM_STR);
            $stmt->bindValue(':email',           trim($data['email']),           PDO::PARAM_STR);
            $stmt->bindValue(':rol',             $data['rol']    ?? 'vendedor',  PDO::PARAM_STR);
            $stmt->bindValue(':estado',          $data['estado'] ?? 'activo',    PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $out = $this->db->query("SELECT @p_id_usuario AS id_usuario, @p_mensaje AS mensaje")
                            ->fetch(PDO::FETCH_ASSOC);

            $idUsuario = (int)($out['id_usuario'] ?? 0);
            $mensaje   = $out['mensaje']           ?? 'Error desconocido';

            return [
                'success'    => $idUsuario > 0,
                'id_usuario' => $idUsuario,
                'mensaje'    => $mensaje,
            ];

        } catch (\PDOException $e) {
            error_log("Usuario::crear — " . $e->getMessage());
            return ['success' => false, 'id_usuario' => 0, 'mensaje' => $this->parsearMensajePDO($e)];
        }
    }

    // =========================================================================
    //  ACTUALIZAR
    //  SP: sp_UpdateUsuario(id, username, password, nombre_completo,
    //                       email, rol, estado, OUT p_mensaje)
    //  Si password viene vacío el SP no lo actualiza.
    // =========================================================================
    public function actualizar(array $data): array
    {
        try {
            $sql = "CALL sp_UpdateUsuario(
                        :id_usuario, :username, :password, :nombre_completo,
                        :email, :rol, :estado,
                        @p_mensaje
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_usuario',      (int)$data['id_usuario'],       PDO::PARAM_INT);
            $stmt->bindValue(':username',        trim($data['username']),        PDO::PARAM_STR);
            // Password vacío = no se cambia (SP lo gestiona)
            $stmt->bindValue(':password',        $data['password'] ?? '',        PDO::PARAM_STR);
            $stmt->bindValue(':nombre_completo', trim($data['nombre_completo']), PDO::PARAM_STR);
            $stmt->bindValue(':email',           trim($data['email']),           PDO::PARAM_STR);
            $stmt->bindValue(':rol',             $data['rol'],                   PDO::PARAM_STR);
            $stmt->bindValue(':estado',          $data['estado'],                PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            $out    = $this->db->query("SELECT @p_mensaje AS mensaje")->fetch(PDO::FETCH_ASSOC);
            $mensaje = $out['mensaje'] ?? 'Error desconocido';

            // El SP devuelve mensaje de éxito si actualizó correctamente
            $success = str_contains($mensaje, 'exitosamente');

            return ['success' => $success, 'mensaje' => $mensaje];

        } catch (\PDOException $e) {
            error_log("Usuario::actualizar — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearMensajePDO($e)];
        }
    }

    // =========================================================================
    //  ELIMINAR (desactivar — soft delete)
    //  SP: sp_DeleteUsuario(p_id_usuario, OUT p_eliminado, OUT p_mensaje)
    // =========================================================================
    public function eliminar(int $id): array
    {
        try {
            $sql = "CALL sp_DeleteUsuario(:id, @p_eliminado, @p_mensaje)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();

            $out      = $this->db->query("SELECT @p_eliminado AS eliminado, @p_mensaje AS mensaje")
                                 ->fetch(PDO::FETCH_ASSOC);
            $eliminado = (bool)($out['eliminado'] ?? false);
            $mensaje   = $out['mensaje']           ?? 'Error desconocido';

            return ['success' => $eliminado, 'mensaje' => $mensaje];

        } catch (\PDOException $e) {
            error_log("Usuario::eliminar — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearMensajePDO($e)];
        }
    }

    // =========================================================================
    //  CAMBIAR CONTRASEÑA
    //  SP: sp_ChangePassword(p_id_usuario, p_password_actual,
    //                        p_password_nueva, OUT p_cambiado, OUT p_mensaje)
    //  Nota: el SP no verifica bcrypt; la verificación del hash actual se hace
    //  en PHP antes de llamar este método (ver UsuarioController::cambiarPassword).
    //  Al SP se le envía la nueva contraseña ya hasheada.
    // =========================================================================
    public function cambiarPassword(int $id, string $passwordActualPlano, string $passwordNuevaHash): array
    {
        try {
            $sql = "CALL sp_ChangePassword(:id, :actual, :nueva, @p_cambiado, @p_mensaje)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id',     $id,                  PDO::PARAM_INT);
            $stmt->bindValue(':actual', $passwordActualPlano,  PDO::PARAM_STR); // SP valida que no esté vacío
            $stmt->bindValue(':nueva',  $passwordNuevaHash,    PDO::PARAM_STR); // ya hasheada
            $stmt->execute();
            $stmt->closeCursor();

            $out     = $this->db->query("SELECT @p_cambiado AS cambiado, @p_mensaje AS mensaje")
                                ->fetch(PDO::FETCH_ASSOC);
            $cambiado = (bool)($out['cambiado'] ?? false);
            $mensaje  = $out['mensaje']          ?? 'Error desconocido';

            return ['success' => $cambiado, 'mensaje' => $mensaje];

        } catch (\PDOException $e) {
            error_log("Usuario::cambiarPassword — " . $e->getMessage());
            return ['success' => false, 'mensaje' => $this->parsearMensajePDO($e)];
        }
    }

    // =========================================================================
    //  PARA SELECT / COMBOS
    //  SP: sp_GetUsuariosForSelect(p_rol)  → value, text
    // =========================================================================
    public function getForSelect(?string $rol = null): array
    {
        try {
            $sql  = "CALL sp_GetUsuariosForSelect(:rol)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':rol', $rol ?: null, PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $rows;

        } catch (\PDOException $e) {
            error_log("Usuario::getForSelect — " . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    //  OBTENER HASH DE CONTRASEÑA (para verificación antes de cambiarla)
    // =========================================================================
    public function getPasswordHash(int $id): ?string
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT password FROM usuarios WHERE id_usuario = :id AND estado = 'activo' LIMIT 1"
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['password'] ?? null;

        } catch (\PDOException $e) {
            error_log("Usuario::getPasswordHash — " . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    //  HELPERS PRIVADOS
    // =========================================================================

    /**
     * Extrae mensajes de negocio lanzados con SIGNAL/RESIGNAL en los SPs.
     * MySQL los devuelve como SQLSTATE 45000 con código de error 1644.
     */
    private function parsearMensajePDO(\PDOException $e): string
    {
        $msg = $e->getMessage();
        if (preg_match('/1644\s+(.+)$/i', $msg, $m)) {
            return trim($m[1]);
        }
        return 'Error interno del servidor';
    }
}