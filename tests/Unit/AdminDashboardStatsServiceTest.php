<?php

namespace Tests\Unit;

use App\Models\Barber;
use App\Models\Category;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use App\Services\AdminDashboard\AdminDashboardStatsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminDashboardStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_statistics_ignore_cancelled_events(): void
    {
        $now = CarbonImmutable::parse('2024-12-15 12:00:00');

        $category = Category::create([
            'name' => 'Стрижка',
            'amount' => 30,
            'color' => 'primary',
        ]);

        $barberUser = User::create([
            'surname' => 'Иванов',
            'name' => 'Иван',
            'patronymic' => 'Иванович',
            'role' => 'barber',
            'email' => 'barber@example.com',
            'number' => '+375000000000',
            'password' => 'password',
        ]);

        Barber::create([
            'user_id' => $barberUser->id,
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'start_working_time' => '09:00',
            'end_working_time' => '18:00',
        ]);

        $clientOne = User::create([
            'surname' => 'Петров',
            'name' => 'Пётр',
            'patronymic' => 'Петрович',
            'role' => 'client',
            'email' => 'client1@example.com',
            'number' => '+375111111111',
            'password' => 'password',
        ]);

        $clientTwo = User::create([
            'surname' => 'Сидоров',
            'name' => 'Сидор',
            'patronymic' => 'Сидорович',
            'role' => 'client',
            'email' => 'client2@example.com',
            'number' => '+375222222222',
            'password' => 'password',
        ]);

        Event::create([
            'id' => Str::uuid()->toString(),
            'subject' => 'Completed visit',
            'category' => $category->id,
            'start' => '2024-12-15 10:00:00',
            'barber_id' => $barberUser->id,
            'organizer_id' => $clientOne->id,
            'status' => Event::STATUS_COMPLETED,
        ]);

        Event::create([
            'id' => Str::uuid()->toString(),
            'subject' => 'Upcoming visit',
            'category' => $category->id,
            'start' => '2024-12-15 12:00:00',
            'barber_id' => $barberUser->id,
            'organizer_id' => $clientTwo->id,
            'status' => Event::STATUS_SCHEDULED,
        ]);

        Event::create([
            'id' => Str::uuid()->toString(),
            'subject' => 'Cancelled visit',
            'category' => $category->id,
            'start' => '2024-12-15 14:00:00',
            'barber_id' => $barberUser->id,
            'organizer_id' => $clientOne->id,
            'status' => Event::STATUS_CANCELLED,
        ]);

        Event::create([
            'id' => Str::uuid()->toString(),
            'subject' => 'Earlier completed visit',
            'category' => $category->id,
            'start' => '2024-12-05 09:00:00',
            'barber_id' => $barberUser->id,
            'organizer_id' => $clientOne->id,
            'status' => Event::STATUS_COMPLETED,
        ]);

        Payment::create([
            'fullname' => 'Пётр Петров',
            'category_id' => $category->id,
            'paid' => true,
            'payment_time' => '10:30:00',
            'payment_date' => '2024-12-15',
            'who_created' => 'client',
        ]);

        $stats = (new AdminDashboardStatsService($now))->getStats();

        $this->assertSame(2, $stats['bookings']['periods']['day']['current']);
        $this->assertSame(1, $stats['bookings']['cancellations']['cancelled']);
        $this->assertSame(1, $stats['bookings']['retention']['clients']);
        $this->assertSame(100.0, $stats['bookings']['retention']['rate']);

        $barberStats = $stats['barbers']['barbers'][0];
        $this->assertSame(3, $barberStats['counts']['month']);
        $this->assertSame(30.0, $barberStats['average_check']);

        $this->assertSame(30.0, $stats['finance']['average_check']);
        $this->assertSame(30.0, $stats['finance']['revenue']['month']);

        $this->assertSame(1, count($stats['barbers']['top_services']));
        $this->assertSame(2, $stats['barbers']['top_services'][0]['count']);
    }
}
