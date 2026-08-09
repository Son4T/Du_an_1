<?php

class ContactModel
{
    public function __construct(private mysqli $conn)
    {
    }

    public function all(string $status = ''): array
    {
        if ($status !== '') {
            $statement = $this->conn->prepare(
                'SELECT * FROM contacts
                WHERE status = ?
                ORDER BY id DESC'
            );
            $statement->bind_param('s', $status);
            $statement->execute();

            return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $this->conn
            ->query('SELECT * FROM contacts ORDER BY id DESC')
            ->fetch_all(MYSQLI_ASSOC);
    }

    public function create(array $contactData): bool
    {
        $statement = $this->conn->prepare(
            "INSERT INTO contacts
                (full_name, email, phone, subject, message, status)
            VALUES (?, ?, ?, ?, ?, 'new')"
        );
        $statement->bind_param(
            'sssss',
            $contactData['full_name'],
            $contactData['email'],
            $contactData['phone'],
            $contactData['subject'],
            $contactData['message']
        );

        return $statement->execute();
    }

    public function status(int $contactId, string $status): bool
    {
        $statement = $this->conn->prepare(
            'UPDATE contacts SET status = ? WHERE id = ?'
        );
        $statement->bind_param('si', $status, $contactId);

        return $statement->execute();
    }

    public function delete(int $contactId): bool
    {
        $statement = $this->conn->prepare(
            'DELETE FROM contacts WHERE id = ?'
        );
        $statement->bind_param('i', $contactId);

        return $statement->execute();
    }
}
