<?php

namespace App\Models;

use App\Core\Model;

/**
 * Disabled-by-default fallback recovery path — only reachable when
 * config('features.security_questions_enabled') is true.
 */
class UserSecurityQuestion extends Model
{
    protected string $table = 'user_security_questions';
    protected string $primaryKey = 'id';

    protected array $fillable = ['user_id', 'question', 'answer_hash'];

    /** @return array<int,array> */
    public function forUser(int $userId): array
    {
        return $this->where(['user_id' => $userId]);
    }

    public function replaceForUser(int $userId, array $questionsAndAnswers): void
    {
        $this->db->query('DELETE FROM `user_security_questions` WHERE `user_id` = :uid', ['uid' => $userId]);
        foreach ($questionsAndAnswers as $qa) {
            $this->insert([
                'user_id'     => $userId,
                'question'    => $qa['question'],
                'answer_hash' => password_hash(mb_strtolower(trim($qa['answer'])), PASSWORD_BCRYPT),
            ]);
        }
    }

    public function verifyAnswers(int $userId, array $answers): bool
    {
        $rows = $this->forUser($userId);
        if (empty($rows) || count($rows) !== count($answers)) {
            return false;
        }
        foreach ($rows as $i => $row) {
            $submitted = mb_strtolower(trim($answers[$i] ?? ''));
            if ($submitted === '' || !password_verify($submitted, $row['answer_hash'])) {
                return false;
            }
        }
        return true;
    }
}
