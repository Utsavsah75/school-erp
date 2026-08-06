<?php

namespace App\Models;

use App\Core\Model;

class StudentTransport extends Model
{
    protected string $table = 'student_transport';
    protected string $primaryKey = 'id';

    protected array $fillable = ['student_id', 'route_id', 'pickup_stop'];

    /** A student's assigned route + pickup stop + vehicle, or false if not enrolled in transport. */
    public function forStudent(int $studentId): array|false
    {
        $sql = "SELECT st.*, tr.route_name, tr.stops, tr.fee_amount,
                       v.vehicle_number, v.driver_name, v.driver_phone
                FROM student_transport st
                JOIN transport_routes tr ON tr.id = st.route_id
                LEFT JOIN vehicles v ON v.id = tr.vehicle_id
                WHERE st.student_id = :sid
                LIMIT 1";
        $rows = $this->raw($sql, ['sid' => $studentId]);
        return $rows[0] ?? false;
    }
}
