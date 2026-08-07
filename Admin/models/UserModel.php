<?php

class UserModel
{
    public function __construct(private mysqli $conn)
    {
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT
                    id,
                    username,
                    email,
                    full_name,
                    phone,
                    address,
                    role,
                    status,
                    created_at
                FROM users
                WHERE 1';

        $parameterTypes = '';
        $parameters = [];

        if (!empty($filters['keyword'])) {
            $sql .= ' AND (
                username LIKE ?
                OR email LIKE ?
                OR full_name LIKE ?
                OR phone LIKE ?
            )';

            $keyword = '%' . $filters['keyword'] . '%';
            $parameterTypes .= 'ssss';
            array_push(
                $parameters,
                $keyword,
                $keyword,
                $keyword,
                $keyword
            );
        }

        if (!empty($filters['role'])) {
            $sql .= ' AND role = ?';
            $parameterTypes .= 's';
            $parameters[] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND status = ?';
            $parameterTypes .= 's';
            $parameters[] = $filters['status'];
        }

        $sql .= ' ORDER BY id DESC';

        $statement = $this->conn->prepare($sql);
        bind_dynamic_params($statement, $parameterTypes, $parameters);
        $statement->execute();

        return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function find(int $userId): ?array
    {
        $statement = $this->conn->prepare(
            'SELECT
                id,
                username,
                email,
                full_name,
                phone,
                address,
                role,
                status,
                created_at
             FROM users
             WHERE id = ?'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();

        return $statement->get_result()->fetch_assoc() ?: null;
    }

    public function findForLogin(string $loginValue): ?array
    {
        $statement = $this->conn->prepare(
            'SELECT *
             FROM users
             WHERE username = ? OR email = ?
             LIMIT 1'
        );
        $statement->bind_param('ss', $loginValue, $loginValue);
        $statement->execute();

        return $statement->get_result()->fetch_assoc() ?: null;
    }

    public function create(array $userData): bool
    {
        $passwordHash = password_hash(
            $userData['password'],
            PASSWORD_DEFAULT
        );

        $statement = $this->conn->prepare(
            'INSERT INTO users (
                username,
                email,
                password_hash,
                full_name,
                phone,
                address,
                role,
                status
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param(
            'ssssssss',
            $userData['username'],
            $userData['email'],
            $passwordHash,
            $userData['full_name'],
            $userData['phone'],
            $userData['address'],
            $userData['role'],
            $userData['status']
        );

        try {
            return $statement->execute();
        } catch (mysqli_sql_exception $error) {
            // Mã lỗi 1062: username hoặc email đã tồn tại.
            if ((int) $error->getCode() === 1062) {
                return false;
            }

            throw $error;
        }
    }

    public function update(
        int $userId,
        array $userData,
        int $currentAdminId = 0
    ): bool {
        // Admin đang đăng nhập không được tự hạ quyền hoặc tự khóa tài khoản.
        $isChangingCurrentAdmin = $userId === $currentAdminId;
        $isRemovingAdminAccess = $userData['role'] !== 'admin'
            || $userData['status'] !== 'active';

        if ($isChangingCurrentAdmin && $isRemovingAdminAccess) {
            return false;
        }

        if (!empty($userData['password'])) {
            $passwordHash = password_hash(
                $userData['password'],
                PASSWORD_DEFAULT
            );

            $statement = $this->conn->prepare(
                'UPDATE users SET
                    username = ?,
                    email = ?,
                    password_hash = ?,
                    full_name = ?,
                    phone = ?,
                    address = ?,
                    role = ?,
                    status = ?
                 WHERE id = ?'
            );
            $statement->bind_param(
                'ssssssssi',
                $userData['username'],
                $userData['email'],
                $passwordHash,
                $userData['full_name'],
                $userData['phone'],
                $userData['address'],
                $userData['role'],
                $userData['status'],
                $userId
            );
        } else {
            $statement = $this->conn->prepare(
                'UPDATE users SET
                    username = ?,
                    email = ?,
                    full_name = ?,
                    phone = ?,
                    address = ?,
                    role = ?,
                    status = ?
                 WHERE id = ?'
            );
            $statement->bind_param(
                'sssssssi',
                $userData['username'],
                $userData['email'],
                $userData['full_name'],
                $userData['phone'],
                $userData['address'],
                $userData['role'],
                $userData['status'],
                $userId
            );
        }

        try {
            return $statement->execute();
        } catch (mysqli_sql_exception $error) {
            if ((int) $error->getCode() === 1062) {
                return false;
            }

            throw $error;
        }
    }

    public function toggle(int $userId, int $currentAdminId = 0): bool
    {
        if ($userId === $currentAdminId) {
            return false;
        }

        $statement = $this->conn->prepare(
            "UPDATE users
             SET status = IF(status = 'active', 'locked', 'active')
             WHERE id = ?"
        );
        $statement->bind_param('i', $userId);

        return $statement->execute() && $statement->affected_rows === 1;
    }

    public function delete(int $userId, int $currentAdminId): bool
    {
        if ($userId === $currentAdminId) {
            return false;
        }

        $user = $this->findRole($userId);

        if (!$user) {
            return false;
        }

        // Luôn giữ tối thiểu một tài khoản quản trị trong hệ thống.
        if ($user['role'] === 'admin' && $this->countAdmins() <= 1) {
            return false;
        }

        // Không xóa người dùng đã có đơn để tránh mất lịch sử bán hàng.
        if ($this->countOrdersByUser($userId) > 0) {
            return false;
        }

        $statement = $this->conn->prepare(
            'DELETE FROM users WHERE id = ?'
        );
        $statement->bind_param('i', $userId);

        return $statement->execute() && $statement->affected_rows === 1;
    }

    private function findRole(int $userId): ?array
    {
        $statement = $this->conn->prepare(
            'SELECT role FROM users WHERE id = ?'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();

        return $statement->get_result()->fetch_assoc() ?: null;
    }

    private function countAdmins(): int
    {
        $result = $this->conn->query(
            "SELECT COUNT(*) AS total
             FROM users
             WHERE role = 'admin'"
        );

        return (int) $result->fetch_assoc()['total'];
    }

    private function countOrdersByUser(int $userId): int
    {
        $statement = $this->conn->prepare(
            'SELECT COUNT(*) AS total
             FROM orders
             WHERE user_id = ?'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();

        return (int) $statement->get_result()->fetch_assoc()['total'];
    }

    public function updateProfile(int $userId, array $profileData): bool
    {
        $statement = $this->conn->prepare(
            'UPDATE users SET
                full_name = ?,
                phone = ?,
                address = ?
             WHERE id = ?'
        );
        $statement->bind_param(
            'sssi',
            $profileData['full_name'],
            $profileData['phone'],
            $profileData['address'],
            $userId
        );

        return $statement->execute();
    }

    public function changePassword(
        int $userId,
        string $currentPassword,
        string $newPassword
    ): bool {
        // Bước 1: Lấy mật khẩu hiện tại trong cơ sở dữ liệu.
        $statement = $this->conn->prepare(
            'SELECT password_hash FROM users WHERE id = ?'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();

        $user = $statement->get_result()->fetch_assoc();

        // Bước 2: Kiểm tra mật khẩu người dùng nhập.
        if (
            !$user
            || !password_verify($currentPassword, $user['password_hash'])
        ) {
            return false;
        }

        // Bước 3: Mã hóa và lưu mật khẩu mới.
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStatement = $this->conn->prepare(
            'UPDATE users SET password_hash = ? WHERE id = ?'
        );
        $updateStatement->bind_param(
            'si',
            $newPasswordHash,
            $userId
        );

        return $updateStatement->execute();
    }
}