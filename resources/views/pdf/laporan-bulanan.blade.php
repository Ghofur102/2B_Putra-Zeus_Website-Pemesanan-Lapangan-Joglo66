<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan Bulanan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 0;
            padding: 24px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #3a5a8c;
            padding-bottom: 16px;
            margin-bottom: 32px;
        }
        .header h1 {
            margin: 0;
            color: #2c4670;
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .header p {
            margin: 8px 0 0;
            color: #4b5563;
            font-size: 14px;
        }
        .header .meta-date {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 6px;
        }
        .section {
            margin-bottom: 32px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c4670;
            background-color: #f0f4f9;
            padding: 8px 12px;
            border-left: 4px solid #3a5a8c;
            margin-bottom: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #f9fafb;
            font-weight: bold;
            color: #374151;
        }
        .w-70 {
            width: 70%;
        }
        .w-30 {
            width: 30%;
        }
        .sub-row {
            padding-left: 24px;
            color: #4b5563;
        }
        .text-right {
            text-align: right;
        }
        .text-green {
            color: #047857;
        }
        .text-red {
            color: #b91c1c;
        }
        .text-blue {
            color: #3a5a8c;
        }
        .total-row td {
            font-weight: bold;
            background-color: #f9fafb;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Laporan Keuangan Joglo66</h1>
        <p>Periode: {{ ucfirst($monthName) }} {{ $year }}</p>
        <p class="meta-date">Dibuat pada: {{ $generateAt }}</p>
    </div>

    <div class="section">
        <div class="section-title">Ringkasan Keuangan</div>
        <table>
            <thead>
                <tr>
                    <th class="w-70">Komponen Ringkasan</th>
                    <th class="w-30 text-right">Jumlah akumulasi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Pemasukan</td>
                    <td class="text-right text-green">Rp {{ number_format($total_income, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Total Pengeluaran</td>
                    <td class="text-right text-red">Rp {{ number_format($total_expense, 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td class="text-blue">Laba Bersih</td>
                    <td class="text-right text-blue">Rp {{ number_format($net_profit, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Rincian Pemasukan</div>
        <table>
            <thead>
                <tr>
                    <th class="w-70">Kategori / Jenis Transaksi</th>
                    <th class="w-30 text-right">Nominal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Penyewaan Lapangan (Total Booking Keseluruhan)</td>
                    <td class="text-right">Rp {{ number_format($income['booking'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="sub-row">- Uang Muka (Down Payment)</td>
                    <td class="text-right">Rp {{ number_format($income['down_payment'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="sub-row">- Pelunasan (Final Payment)</td>
                    <td class="text-right">Rp {{ number_format($income['final_payment'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Penyewaan Lapangan Batal (DP Hangus)</td>
                    <td class="text-right">Rp {{ number_format($income['forsaken_downpayment'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Penyewaan Atribut (Rompi, Bola, Sepatu, dll)</td>
                    <td class="text-right">Rp {{ number_format($income['attribute_rental'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td>Total Pemasukan</td>
                    <td class="text-right text-green">Rp {{ number_format($total_income, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Rincian Pengeluaran</div>
        <table>
            <thead>
                <tr>
                    <th class="w-70">Kategori / Jenis Transaksi</th>
                    <th class="w-30 text-right">Nominal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Biaya Operasional Lapangan</td>
                    <td class="text-right">Rp {{ number_format($expense['operational'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Pembayaran Gaji Karyawan</td>
                    <td class="text-right">Rp {{ number_format($expense['salary'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td>Total Pengeluaran</td>
                    <td class="text-right text-red">Rp {{ number_format($total_expense, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

</body>
</html>
