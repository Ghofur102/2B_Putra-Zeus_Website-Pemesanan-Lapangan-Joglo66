<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Booking Lapangan Olahraga</title>
    <style>
        :root {
            --color-primary: #3a5a8c;
            --color-success: #22c55e;
            --color-danger: #ef4444;
            --color-warning: #eab308;
            --color-info: #3b82f6;
            --color-bg: #ffffff;
            --color-text: #1f2937;
            --color-border: #e5e7eb;
            --color-card-bg: #f9fafb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
            color: var(--color-text);
            background-color: var(--color-card-bg);
        }

        /* ===== NAVBAR ===== */
        .navbar {
            background-color: var(--color-primary);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-logo {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
        }

        .navbar-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .navbar-links a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s ease;
            font-size: 0.95rem;
        }

        .navbar-links a:hover {
            opacity: 0.8;
        }

        /* ===== MAIN CONTENT ===== */
        .container {
            max-width: 860px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* ===== INFORMASI LAPANGAN SECTION ===== */
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--color-text);
        }

        .info-lapangan {
            background-color: var(--color-bg);
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 2rem;
            border: 0.5px solid var(--color-border);
        }

        .info-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .info-left {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 500;
            color: var(--color-text);
        }

        .form-group select {
            padding: 0.75rem;
            border: 0.5px solid var(--color-border);
            border-radius: 0.375rem;
            font-size: 0.95rem;
            cursor: pointer;
            background-color: var(--color-bg);
            color: var(--color-text);
        }

        .form-group select:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(58, 90, 140, 0.1);
        }

        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            width: fit-content;
        }

        .status-badge.available {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-badge.unavailable {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .btn-order {
            background-color: var(--color-primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
            align-self: flex-start;
            text-decoration: none;
            display: inline-block;
        }

        .btn-order:hover {
            background-color: #2c4670;
        }

        .info-right {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .field-image {
            width: 100%;
            height: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
            overflow: hidden;
        }

        .field-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ===== BOOKING TERDEKAT SECTION ===== */
        .booking-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .booking-card {
            background-color: var(--color-bg);
            border: 0.5px solid var(--color-border);
            border-radius: 0.375rem;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .card-date {
            font-size: 0.95rem;
            color: var(--color-text);
        }

        .card-date strong {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .card-time {
            font-size: 0.9rem;
            color: #6b7280;
            line-height: 1.4;
        }

        .card-team {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 500;
        }

        .card-status {
            font-size: 0.85rem;
            color: var(--color-info);
            font-weight: 500;
        }

        .card-status.done {
            color: var(--color-success);
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 2rem;
            color: #9ca3af;
            background-color: var(--color-card-bg);
            border: 0.5px dashed var(--color-border);
            border-radius: 0.375rem;
        }

        /* ===== RIWAYAT SECTION ===== */
        .history-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .booking-grid,
            .history-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .info-content {
                grid-template-columns: 1fr;
            }

            .navbar-links {
                gap: 1rem;
            }

            .navbar-links a {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 640px) {
            .booking-grid,
            .history-grid {
                grid-template-columns: 1fr;
            }

            .navbar {
                flex-direction: column;
                gap: 1rem;
            }

            .navbar-links {
                flex-direction: column;
                gap: 0.5rem;
                width: 100%;
            }

            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <a href="#" class="navbar-logo">Beranda</a>
        <ul class="navbar-links">
            <li><a href="{{ route('tenant.booking.schedule') }}">Booking Lapangan</a></li>
            <li><a href="#bookings">Jadwal Lapangan</a></li>
            <li><a href="#history">Lihat Riwayat Transaksi</a></li>
        </ul>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="container">
        <!-- INFORMASI LAPANGAN -->
        <h2 class="section-title">Informasi Lapangan</h2>
        <div class="info-lapangan">
            <div class="info-content">
                <div class="info-left">
                    <div class="form-group">
                        <label for="fieldSelect">Pilih Lapangan</label>
                        <select id="fieldSelect" onchange="handleFieldChange(event)">
                            <option value="">-- Pilih Lapangan --</option>
                            @foreach($fields as $field)
                                <option value="{{ $field->id }}" data-image="{{ $field->image_url }}" data-status="tersedia">
                                    {{ $field->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="statusContainer" style="display: none;">
                        <span id="statusBadge" class="status-badge available">✓ Tersedia</span>
                    </div>
                    @if($selectedFieldId)
                        <a href="{{ route('tenant.booking.schedule', ['field_id' => $selectedFieldId]) }}" class="btn-order">Pesan Now</a>
                    @else
                        <a href="{{ route('tenant.booking.dashboard') }}" class="btn-order" onclick="alert('Pilih lapangan terlebih dahulu'); return false;">Pesan Now</a>
                    @endif
                </div>
                <div class="info-right">
                    <div id="fieldImageContainer" class="field-image">
                        <span>Pilih lapangan untuk melihat gambar</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- BOOKING TERDEKAT -->
        <h2 class="section-title" id="bookings">Booking Terdekat</h2>
        <div class="booking-grid" id="bookingGrid">
            @if($selectedField && $nearestBookings->isNotEmpty())
                @foreach($nearestBookings as $detail)
                    @php
                        $playDate = \Carbon\Carbon::parse($detail->play_date);
                        $now = \Carbon\Carbon::now();
                        $diff = $now->diff($playDate);
                        $dayName = $playDate->format('l');
                        $dayShort = substr($dayName, 0, 3);
                    @endphp
                    <div class="booking-card">
                        <div class="card-date">
                            <strong>{{ $dayShort }} {{ $playDate->format('d') }}</strong><br>
                            {{ $playDate->format('Y') }}
                        </div>
                        <div class="card-time">{{ $detail->start_play_time }}<br>{{ $detail->end_play_time }}</div>
                        <div class="card-team">{{ $detail->booking->team_name }}</div>
                        <div class="card-status">Mulai dalam 2 jam</div>
                    </div>
                @endforeach
            @else
                <div class="empty-state">Tidak ada booking tersedia</div>
            @endif
        </div>

        <!-- RIWAYAT -->
        <h2 class="section-title" id="history">Riwayat</h2>
        <div class="history-grid" id="historyGrid">
            @if($selectedField && $userBookings->isNotEmpty())
                @foreach($userBookings as $booking)
                    @php
                        $bookingDate = \Carbon\Carbon::parse($booking->booking_date);
                        $dayName = $bookingDate->format('l');
                        $dayShort = substr($dayName, 0, 3);
                        $firstDetail = $booking->details->first();
                    @endphp
                    <div class="booking-card">
                        <div class="card-date">
                            <strong>{{ $dayShort }} {{ $bookingDate->format('d') }}</strong><br>
                            {{ $bookingDate->format('Y') }}
                        </div>
                        <div class="card-time">{{ $firstDetail->start_play_time }}<br>{{ $firstDetail->end_play_time }}</div>
                        <div class="card-team">{{ $booking->team_name }}</div>
                        <div class="card-status done">✓ Done</div>
                    </div>
                @endforeach
            @else
                <div class="empty-state">Riwayat belum tersedia</div>
            @endif
        </div>
    </div>

    <script>
        function handleFieldChange(event) {
            const selectedOption = event.target.options[event.target.selectedIndex];
            const fieldId = event.target.value;
            const imageUrl = selectedOption.getAttribute('data-image');

            if (fieldId) {
                // Update image
                const imageContainer = document.getElementById('fieldImageContainer');
                if (imageUrl) {
                    imageContainer.innerHTML = `<img src="${imageUrl}" alt="Field Image">`;
                } else {
                    imageContainer.innerHTML = '<span>Gambar tidak tersedia</span>';
                }

                // Show status
                document.getElementById('statusContainer').style.display = 'block';
                
                // Redirect to dashboard with field_id
                window.location.href = `{{ route('tenant.booking.dashboard') }}?field_id=${fieldId}`;
            } else {
                // Reset
                document.getElementById('fieldImageContainer').innerHTML = '<span>Pilih lapangan untuk melihat gambar</span>';
                document.getElementById('statusContainer').style.display = 'none';
            }
        }

        // Initialize on page load
        function initializeFieldDisplay() {
            const fieldSelect = document.getElementById('fieldSelect');
            @if($selectedFieldId)
                fieldSelect.value = {{ $selectedFieldId }};
                // Show status container when field is already selected on page load
                document.getElementById('statusContainer').style.display = 'block';
                
                // Also update image
                const selectedOption = fieldSelect.options[fieldSelect.selectedIndex];
                const imageUrl = selectedOption.getAttribute('data-image');
                const imageContainer = document.getElementById('fieldImageContainer');
                if (imageUrl) {
                    imageContainer.innerHTML = `<img src="${imageUrl}" alt="Field Image">`;
                } else {
                    imageContainer.innerHTML = '<span>Gambar tidak tersedia</span>';
                }
            @endif
        }

        // Call initialization when page loads
        document.addEventListener('DOMContentLoaded', initializeFieldDisplay);
    </script>
</body>
</html>
