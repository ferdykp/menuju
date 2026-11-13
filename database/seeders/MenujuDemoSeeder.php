<?php

namespace Database\Seeders;

use App\Models\BudgetCategory;
use App\Models\Event;
use App\Models\EventAgendaItem;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Guest;
use App\Models\GuestInvitationToken;
use App\Models\Invitation;
use App\Models\InvitationGalleryImage;
use App\Models\Milestone;
use App\Models\Need;
use App\Models\PaymentObligation;
use App\Models\PaymentRecord;
use App\Models\SavingsDeposit;
use App\Models\SavingsGoal;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MenujuDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('MenujuDemoSeeder hanya boleh dijalankan pada environment local atau testing.');
        }

        DB::transaction(function (): void {
            $today = today('Asia/Jakarta');
            $owner = User::updateOrCreate(
                ['email' => 'ayu@demo.menuju.test'],
                ['name' => 'Ayu Pratama', 'password' => config('menuju.demo_seed_password', 'password'), 'email_verified_at' => now()],
            );
            $partner = User::updateOrCreate(
                ['email' => 'bima@demo.menuju.test'],
                ['name' => 'Bima Santoso', 'password' => config('menuju.demo_seed_password', 'password'), 'email_verified_at' => now()],
            );

            $workspace = Workspace::withTrashed()->updateOrCreate(
                ['owner_id' => $owner->id, 'name' => 'Ayu & Bima'],
                [
                    'partner_one_name' => 'Ayu',
                    'partner_two_name' => 'Bima',
                    'wedding_date' => $today->copy()->addMonths(8)->toDateString(),
                    'timezone' => 'Asia/Jakarta',
                    'onboarding_completed_at' => now(),
                ],
            );
            if ($workspace->trashed()) {
                $workspace->restore();
            }
            $workspace->forceFill(['onboarding_completed_at' => now()])->save();

            DB::table('workspace_members')->updateOrInsert(
                ['workspace_id' => $workspace->id, 'user_id' => $owner->id],
                ['role' => 'owner', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            );
            DB::table('workspace_members')->updateOrInsert(
                ['workspace_id' => $workspace->id, 'user_id' => $partner->id],
                ['role' => 'partner', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            );
            WorkspaceInvitation::updateOrCreate(
                ['workspace_id' => $workspace->id, 'email' => 'partner.pending@demo.menuju.test'],
                [
                    'invited_by' => $owner->id,
                    'token_hash' => hash('sha256', hash_hmac('sha256', 'pending-workspace-invitation', (string) config('app.key'))),
                    'expires_at' => $today->copy()->addDays(7),
                    'accepted_at' => null,
                ],
            );

            $milestones = $this->seedMilestones($workspace, $owner, $today);
            $this->seedTasks($workspace, $owner, $partner, $milestones, $today);
            $events = $this->seedEvents($workspace, $owner, $today);
            $guests = $this->seedGuests($workspace, $owner);
            $categories = $this->seedBudgets($workspace, $events);
            $accounts = $this->seedAccounts($workspace, $owner);
            $transactions = $this->seedTransactions($workspace, $owner, $accounts, $categories, $today);
            $this->seedObligations($workspace, $categories, $events, $transactions, $today);
            $goals = $this->seedSavings($workspace, $owner, $accounts, $today);
            $this->seedNeeds($workspace, $owner, $categories, $events, $today);
            $invitation = $this->seedInvitation($workspace, $owner, $guests);
            $this->seedGallery($invitation);
        });
    }

    private function seedMilestones(Workspace $workspace, User $owner, $today): array
    {
        $rows = [
            'Tentukan konsep pernikahan' => ['category' => 'Perencanaan', 'days' => -14, 'status' => 'done'],
            'Pilih venue dan tanggal' => ['category' => 'Venue', 'days' => 12, 'status' => 'in_progress'],
            'Finalisasi vendor utama' => ['category' => 'Vendor', 'days' => 45, 'status' => 'upcoming'],
            'Kirim undangan' => ['category' => 'Undangan', 'days' => 150, 'status' => 'upcoming'],
            'Konfirmasi jumlah tamu' => ['category' => 'Tamu', 'days' => 190, 'status' => 'upcoming'],
        ];
        $milestones = [];
        foreach ($rows as $title => $row) {
            $milestones[$title] = $this->restoreIfDeleted(Milestone::withTrashed()->updateOrCreate(
                ['workspace_id' => $workspace->id, 'title' => $title],
                [
                    'created_by' => $owner->id,
                    'description' => 'Langkah persiapan bersama untuk hari istimewa Ayu dan Bima.',
                    'category' => $row['category'],
                    'target_date' => $today->copy()->addDays($row['days'])->toDateString(),
                    'status' => $row['status'],
                    'sort_order' => count($milestones),
                ],
            ));
        }

        return $milestones;
    }

    private function seedTasks(Workspace $workspace, User $owner, User $partner, array $milestones, $today): void
    {
        $rows = [
            ['Diskusikan referensi dekorasi', 'Dekorasi', 'high', 'in_progress', 5, 'Pilih tiga referensi yang paling disukai.'],
            ['Tanyakan paket katering', 'Katering', 'normal', 'planned', 10, 'Minta pilihan menu dan jumlah porsi.'],
            ['Susun daftar keluarga inti', 'Tamu', 'normal', 'completed', -2, 'Daftar awal sudah disepakati.'],
            ['Bandingkan tiga venue', 'Venue', 'high', 'planned', 12, 'Bandingkan kapasitas, akses, dan biaya.'],
            ['Pilih fotografer', 'Vendor', 'low', 'planned', 28, 'Simpan portofolio dan paket harga.'],
            ['Periksa dokumen administrasi', 'Administrasi', 'normal', 'planned', 35, 'Catat dokumen yang perlu disiapkan.'],
            ['Tentukan palet warna', 'Konsep', 'low', 'completed', -7, 'Palet sage dan ivory menjadi pilihan awal.'],
            ['Buat daftar lagu acara', 'Acara', 'normal', 'planned', 90, 'Kumpulkan lagu untuk akad dan resepsi.'],
        ];

        foreach ($rows as $index => [$title, $category, $priority, $status, $days, $description]) {
            $this->restoreIfDeleted(Task::withTrashed()->updateOrCreate(
                ['workspace_id' => $workspace->id, 'title' => $title],
                [
                    'milestone_id' => $index < 2 ? $milestones['Pilih venue dan tanggal']->id : $milestones['Finalisasi vendor utama']->id,
                    'created_by' => $owner->id,
                    'assignee_id' => $index % 2 ? $partner->id : $owner->id,
                    'description' => $description,
                    'category' => $category,
                    'due_at' => $today->copy()->addDays($days)->toDateString(),
                    'status' => $status,
                    'priority' => $priority,
                    'completed_at' => $status === 'completed' ? $today->copy()->addDays($days)->setTime(12, 0) : null,
                ],
            ));
        }
    }

    private function seedEvents(Workspace $workspace, User $owner, $today): array
    {
        $events = [];
        $rows = [
            'Akad Nikah' => [
                'event_type' => 'ceremony', 'days' => 240, 'start_time' => '08:00', 'end_time' => '10:00',
                'venue_name' => 'Pendopo Taman Sari', 'address' => 'Jl. Kenanga No. 18, Yogyakarta',
                'notes' => 'Keluarga inti hadir 30 menit sebelum acara.',
                'agenda' => [
                    ['Kedatangan keluarga', '07:30', '08:00', 'Area depan'],
                    ['Pembukaan dan akad', '08:00', '09:00', 'Pendopo utama'],
                    ['Sesi foto keluarga', '09:00', '10:00', 'Taman samping'],
                ],
            ],
            'Resepsi Pernikahan' => [
                'event_type' => 'reception', 'days' => 240, 'start_time' => '11:00', 'end_time' => '14:00',
                'venue_name' => 'Pendopo Taman Sari', 'address' => 'Jl. Kenanga No. 18, Yogyakarta',
                'notes' => 'Konfirmasi kebutuhan akses parkir vendor.',
                'agenda' => [
                    ['Penerimaan tamu', '11:00', '12:00', 'Lobi utama'],
                    ['Kedatangan pengantin', '12:00', '12:30', 'Pendopo utama'],
                    ['Makan siang dan ramah tamah', '12:30', '14:00', 'Area resepsi'],
                ],
            ],
            'Lamaran Keluarga' => [
                'event_type' => 'engagement', 'days' => -30, 'start_time' => '10:00', 'end_time' => '12:00',
                'venue_name' => 'Rumah keluarga Ayu', 'address' => 'Yogyakarta',
                'notes' => 'Acara keluarga telah selesai.',
                'agenda' => [],
            ],
        ];

        foreach ($rows as $title => $row) {
            $events[$title] = $this->restoreIfDeleted(Event::withTrashed()->updateOrCreate(
                ['workspace_id' => $workspace->id, 'title' => $title],
                [
                    'created_by' => $owner->id, 'event_type' => $row['event_type'],
                    'event_date' => $today->copy()->addDays($row['days'])->toDateString(),
                    'start_time' => $row['start_time'], 'end_time' => $row['end_time'],
                    'timezone' => 'Asia/Jakarta', 'venue_name' => $row['venue_name'],
                    'address' => $row['address'], 'notes' => $row['notes'],
                ],
            ));
            foreach ($row['agenda'] as $index => [$agendaTitle, $start, $end, $location]) {
                EventAgendaItem::updateOrCreate(
                    ['event_id' => $events[$title]->id, 'title' => $agendaTitle],
                    ['start_time' => $start, 'end_time' => $end, 'location' => $location, 'notes' => null, 'sort_order' => $index],
                );
            }
        }

        return $events;
    }

    private function seedGuests(Workspace $workspace, User $owner): array
    {
        $rows = [
            ['Nadia Putri', 'nadia@demo.menuju.test', 'Teman kuliah', 'bride', 'sent', 'attending', 1, 1],
            ['Dimas Pratama', 'dimas@demo.menuju.test', 'Sahabat', 'groom', 'sent', 'pending', 1, 0],
            ['Keluarga Pratama', 'keluarga.pratama@demo.menuju.test', 'Keluarga', 'groom', 'sent', 'attending', 2, 1],
            ['Rani dan Fajar', 'rani.fajar@demo.menuju.test', 'Teman kerja', 'bride', 'not_sent', 'pending', 1, 0],
            ['Tante Mira', 'mira@demo.menuju.test', 'Keluarga', 'bride', 'sent', 'declined', 0, 0],
            ['Rekan kantor Bima', 'kantor.bima@demo.menuju.test', 'Rekan kerja', 'groom', 'not_sent', 'pending', 0, 0],
            ['Salsa Maharani', 'salsa@demo.menuju.test', 'Teman komunitas', 'shared', 'sent', 'attending', 0, 0],
            ['Pak Rudi', 'rudi@demo.menuju.test', 'Tetangga', 'shared', 'not_sent', 'pending', 0, 0],
        ];
        $guests = [];
        foreach ($rows as [$name, $email, $group, $side, $invitationStatus, $rsvpStatus, $plusOnes, $rsvpPlusOnes]) {
            $guests[$email] = $this->restoreIfDeleted(Guest::withTrashed()->updateOrCreate(
                ['workspace_id' => $workspace->id, 'email' => $email],
                [
                    'created_by' => $owner->id, 'name' => $name, 'phone' => null,
                    'group_name' => $group, 'side' => $side, 'invitation_status' => $invitationStatus,
                    'rsvp_status' => $rsvpStatus, 'plus_ones' => $plusOnes,
                    'rsvp_plus_ones' => $rsvpStatus === 'attending' ? $rsvpPlusOnes : 0,
                    'notes' => 'Data contoh lokal untuk pengembangan MENUJU.',
                ],
            ));
        }

        return $guests;
    }

    private function seedBudgets(Workspace $workspace, array $events): array
    {
        $rows = [
            'Gedung & Lokasi' => [18_000_000, 'akad'],
            'Katering' => [22_000_000, 'resepsi'],
            'Dekorasi' => [12_000_000, 'resepsi'],
            'Busana & Rias' => [8_000_000, 'akad'],
            'Dokumentasi' => [7_000_000, 'resepsi'],
        ];
        $categories = [];
        foreach ($rows as $name => [$plannedAmount, $event]) {
            $categories[$name] = BudgetCategory::updateOrCreate(
                ['workspace_id' => $workspace->id, 'name' => $name],
                [
                    'event_id' => $events[$event === 'akad' ? 'Akad Nikah' : 'Resepsi Pernikahan']->id,
                    'planned_amount' => $plannedAmount,
                    'color' => ['#71836A', '#C18D75', '#D6B65A', '#8A9B9A', '#B48A94'][count($categories)],
                    'notes' => 'Rencana awal; sesuaikan dengan kesepakatan pasangan.',
                ],
            );
        }

        return $categories;
    }

    private function seedAccounts(Workspace $workspace, User $owner): array
    {
        $rows = [
            'Kas bersama' => ['cash', null, 5_000_000, false],
            'Rekening bersama' => ['bank', 'Bank Nusantara', 20_000_000, false],
            'Rekening cadangan' => ['bank', 'Bank Sejahtera', 2_500_000, true],
        ];
        $accounts = [];
        foreach ($rows as $name => [$type, $institution, $opening, $archived]) {
            $accounts[$name] = FinancialAccount::updateOrCreate(
                ['workspace_id' => $workspace->id, 'name' => $name],
                [
                    'created_by' => $owner->id, 'account_type' => $type, 'institution' => $institution,
                    'opening_balance' => $opening, 'currency' => 'IDR', 'is_archived' => $archived,
                ],
            );
        }

        return $accounts;
    }

    private function seedTransactions(Workspace $workspace, User $owner, array $accounts, array $categories, $today): array
    {
        $rows = [
            'DEMO-INCOME-001' => ['income', 'Pemasukan', 'Dana awal keluarga', 'Rekening bersama', null, 15_000_000, -20],
            'DEMO-EXPENSE-001' => ['expense', 'Gedung & Lokasi', 'Uang tanda jadi venue', 'Kas bersama', null, 3_000_000, -12],
            'DEMO-EXPENSE-002' => ['expense', 'Katering', 'Uji menu katering', 'Kas bersama', null, 1_250_000, -8],
            'DEMO-TRANSFER-001' => ['transfer', null, 'Pindah dana ke rekening', 'Kas bersama', 'Rekening bersama', 750_000, -5],
        ];
        $transactions = [];
        foreach ($rows as $reference => [$type, $category, $description, $account, $destination, $amount, $days]) {
            $transactions[$reference] = FinancialTransaction::updateOrCreate(
                ['workspace_id' => $workspace->id, 'reference' => $reference],
                [
                    'created_by' => $owner->id, 'account_id' => $accounts[$account]->id,
                    'destination_account_id' => $destination ? $accounts[$destination]->id : null,
                    'savings_goal_id' => null, 'type' => $type,
                    'category' => $category ? ($categories[$category]->name ?? $category) : null,
                    'description' => $description, 'amount' => $amount,
                    'occurred_at' => $today->copy()->addDays($days)->toDateString(),
                    'notes' => 'Transaksi seed lokal untuk pratinjau data keuangan.', 'voided_at' => null,
                ],
            );
        }

        return $transactions;
    }

    private function seedObligations(Workspace $workspace, array $categories, array $events, array $transactions, $today): void
    {
        $paid = PaymentObligation::withTrashed()->updateOrCreate(
            ['workspace_id' => $workspace->id, 'title' => 'Termin pertama venue'],
            [
                'budget_category_id' => $categories['Gedung & Lokasi']->id,
                'event_id' => $events['Akad Nikah']->id, 'vendor_name' => 'Pendopo Taman Sari',
                'total_amount' => 6_000_000, 'due_at' => $today->copy()->addDays(20)->toDateString(),
                'status' => 'unpaid', 'notes' => 'Dibayar bertahap sesuai perjanjian vendor.',
            ],
        );
        if ($paid->trashed()) $paid->restore();
        PaymentRecord::updateOrCreate(
            ['payment_obligation_id' => $paid->id, 'financial_transaction_id' => $transactions['DEMO-EXPENSE-001']->id],
            ['amount' => 3_000_000, 'paid_at' => $today->copy()->subDays(12)->toDateString(), 'notes' => 'Uang tanda jadi.'],
        );

        PaymentObligation::withTrashed()->updateOrCreate(
            ['workspace_id' => $workspace->id, 'title' => 'Pelunasan katering'],
            [
                'budget_category_id' => $categories['Katering']->id,
                'event_id' => $events['Resepsi Pernikahan']->id, 'vendor_name' => 'Dapur Rasa',
                'total_amount' => 8_000_000, 'due_at' => $today->copy()->addDays(190)->toDateString(),
                'status' => 'unpaid', 'notes' => 'Pembayaran akhir mendekati hari acara.',
            ],
        );
    }

    private function seedSavings(Workspace $workspace, User $owner, array $accounts, $today): array
    {
        $goal = SavingsGoal::updateOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'Dana darurat acara'],
            [
                'created_by' => $owner->id, 'target_amount' => 10_000_000,
                'target_date' => $today->copy()->addMonths(6)->toDateString(),
                'notes' => 'Dana cadangan untuk kebutuhan tak terduga.', 'is_archived' => false,
            ],
        );
        $archived = SavingsGoal::updateOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'Bulan madu'],
            [
                'created_by' => $owner->id, 'target_amount' => 15_000_000,
                'target_date' => $today->copy()->addMonths(10)->toDateString(),
                'notes' => 'Target contoh yang diarsipkan.', 'is_archived' => true,
            ],
        );
        SavingsDeposit::updateOrCreate(
            ['savings_goal_id' => $goal->id, 'notes' => 'Setoran awal dari rekening bersama.'],
            [
                'financial_transaction_id' => FinancialTransaction::updateOrCreate(
                    ['workspace_id' => $workspace->id, 'reference' => 'DEMO-SAVINGS-001'],
                    [
                        'created_by' => $owner->id, 'account_id' => $accounts['Rekening bersama']->id,
                        'destination_account_id' => null, 'savings_goal_id' => null,
                        'type' => 'savings_contribution', 'category' => 'Tabungan',
                        'description' => 'Setoran tabungan: Dana darurat acara', 'amount' => 1_000_000,
                        'occurred_at' => $today->copy()->subDays(3)->toDateString(),
                        'reference' => 'DEMO-SAVINGS-001', 'notes' => 'Setoran dari rekening bersama.', 'voided_at' => null,
                    ],
                )->id,
                'amount' => 1_000_000, 'deposited_at' => $today->copy()->subDays(3)->toDateString(),
                'reversed_at' => null,
            ],
        );
        SavingsDeposit::updateOrCreate(
            ['savings_goal_id' => $goal->id, 'notes' => 'Setoran tunai tanpa akun sumber.'],
            [
                'financial_transaction_id' => null, 'amount' => 500_000,
                'deposited_at' => $today->copy()->subDays(1)->toDateString(), 'reversed_at' => null,
            ],
        );
        SavingsDeposit::updateOrCreate(
            ['savings_goal_id' => $archived->id, 'notes' => 'Riwayat target arsip.'],
            [
                'financial_transaction_id' => null, 'amount' => 250_000,
                'deposited_at' => $today->copy()->subDays(40)->toDateString(), 'reversed_at' => null,
            ],
        );

        return ['active' => $goal, 'archived' => $archived];
    }

    private function seedNeeds(Workspace $workspace, User $owner, array $categories, array $events, $today): void
    {
        $rows = [
            ['Fotografer akad', 'Dokumentasi', 'Lensa Kita Studio', 'Dokumentasi', 1, 6_500_000, null, 'planned', 'high', 35],
            ['Dekorasi pelaminan', 'Dekorasi', 'Ruang Rona', 'Dekorasi', 1, 9_000_000, null, 'shortlisted', 'high', 50],
            ['Paket katering keluarga', 'Konsumsi', 'Dapur Rasa', 'Katering', 250, 85_000, null, 'planned', 'normal', 70],
            ['Uang tanda jadi gedung', 'Venue', 'Pendopo Taman Sari', 'Gedung & Lokasi', 1, 3_000_000, 3_000_000, 'purchased', 'high', -12],
            ['Undangan cetak keluarga', 'Undangan', null, null, 80, 12_500, null, 'planned', 'low', 120],
        ];
        foreach ($rows as [$title, $category, $vendor, $budget, $quantity, $estimate, $purchasedAmount, $status, $priority, $days]) {
            $needCategory = $workspace->needCategories()->firstOrCreate(['name' => $category]);
            $eventTitle = match ($title) {
                'Fotografer akad', 'Uang tanda jadi gedung' => 'Akad Nikah',
                'Dekorasi pelaminan', 'Paket katering keluarga' => 'Resepsi Pernikahan',
                default => null,
            };
            $this->restoreIfDeleted(Need::withTrashed()->updateOrCreate(
                ['workspace_id' => $workspace->id, 'title' => $title],
                [
                    'created_by' => $owner->id, 'event_id' => $eventTitle ? $events[$eventTitle]->id : null,
                    'category' => $category, 'need_category_id' => $needCategory->id, 'vendor_name' => $vendor,
                    'budget_category' => $budget, 'budget_category_id' => $budget ? $categories[$budget]->id : null,
                    'quantity' => $quantity, 'estimated_amount' => $estimate, 'purchased_amount' => $purchasedAmount,
                    'needed_by' => $today->copy()->addDays($days)->toDateString(),
                    'purchased_at' => $status === 'purchased' ? $today->copy()->subDays(12)->toDateString() : null,
                    'status' => $status, 'priority' => $priority,
                    'notes' => 'Kebutuhan contoh untuk workspace demo.',
                ],
            ));
        }
    }

    private function seedInvitation(Workspace $workspace, User $owner, array $guests): Invitation
    {
        $coverPath = "invitations/demo-{$workspace->id}/cover.webp";
        Storage::disk('local')->delete("invitations/demo-{$workspace->id}/cover.png");
        if (is_file(public_path('images/invitations/botanical-preview.webp'))) {
            Storage::disk('local')->put($coverPath, file_get_contents(public_path('images/invitations/botanical-preview.webp')));
        }

        $invitation = Invitation::updateOrCreate(
            ['workspace_id' => $workspace->id],
            [
                'created_by' => $owner->id,
                'title' => 'Undangan Ayu & Bima',
                'slug' => 'demo-ayu-bima-'.$workspace->id,
                'theme_key' => 'botanical',
                'cover_title' => 'Ayu & Bima',
                'cover_image_path' => is_file(public_path('images/invitations/botanical-preview.webp')) ? $coverPath : null,
                'greeting' => 'Dengan penuh rasa syukur, kami mengundang Anda untuk merayakan hari istimewa kami.',
                'story' => 'Kami bertemu melalui sahabat yang sama. Dari percakapan sederhana, tumbuh cerita yang ingin kami lanjutkan bersama.',
                'quote' => 'Dua perjalanan, satu tujuan.',
                'closing' => 'Merupakan kebahagiaan bagi kami apabila Anda berkenan hadir.',
                'published_at' => null,
            ],
        );

        $guest = $guests['nadia@demo.menuju.test'];
        GuestInvitationToken::updateOrCreate(
            ['invitation_id' => $invitation->id, 'guest_id' => $guest->id],
            ['token_hash' => hash('sha256', hash_hmac('sha256', 'demo-rsvp-nadia', (string) config('app.key')))],
        );

        return $invitation;
    }

    private function restoreIfDeleted(Model $model): Model
    {
        if (method_exists($model, 'trashed') && $model->trashed()) {
            $model->restore();
        }

        return $model;
    }

    private function seedGallery(Invitation $invitation): void
    {
        $legacyPaths = [
            "invitations/demo-{$invitation->id}/gallery-1.png",
            "invitations/demo-{$invitation->id}/gallery-2.png",
        ];
        $invitation->galleryImages()->whereIn('image_path', $legacyPaths)->get()->each(function ($image): void {
            Storage::disk('local')->delete($image->image_path);
            $image->delete();
        });

        $gallery = [
            ['public/images/invitations/botanical-preview.webp', 'Awal cerita Ayu dan Bima'],
            ['public/images/invitations/editorial-preview.webp', 'Momen yang ingin dikenang'],
        ];
        foreach ($gallery as $index => [$source, $alt]) {
            if (! is_file(base_path($source))) {
                continue;
            }
            $path = "invitations/demo-{$invitation->id}/gallery-".($index + 1).'.webp';
            Storage::disk('local')->put($path, file_get_contents(base_path($source)));
            InvitationGalleryImage::updateOrCreate(
                ['invitation_id' => $invitation->id, 'image_path' => $path],
                ['alt_text' => $alt, 'sort_order' => $index],
            );
        }
    }
}
