<?php
/**
 * Session Handler via Banco de Dados (TiDB Cloud)
 * Necessário para Vercel (serverless) onde sessões de arquivo não persistem.
 */
class DbSessionHandler implements SessionHandlerInterface
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = ? AND expires_at > NOW()");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['data'] : '';
    }

    public function write(string $id, string $data): bool
    {
        $expires = date('Y-m-d H:i:s', time() + 86400); // 24h
        $stmt = $this->pdo->prepare(
            "REPLACE INTO sessions (id, data, expires_at) VALUES (?, ?, ?)"
        );
        return $stmt->execute([$id, $data, $expires]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE expires_at < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    }
}

/**
 * Inicializa sessão via banco de dados.
 * Chamar ANTES de session_start().
 */
function init_db_session(PDO $pdo): void
{
    // Criar tabela se não existir
    static $tableChecked = false;
    if (!$tableChecked) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(128) PRIMARY KEY,
            data TEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            INDEX idx_sessions_expires (expires_at)
        )");
        $tableChecked = true;
    }

    $handler = new DbSessionHandler($pdo);
    session_set_save_handler($handler, true);
}
