<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan Bulanan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1B4F8A;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #1B4F8A;
            font-size: 22px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0;
            color: #666666;
            font-size: 14px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1B4F8A;
            background-color: #E6F1FB;
            padding: 8px 12px;
            border-left: 4px solid #1B4F8A;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px 12px;
            border: 1px solid #D3D1C7;
            text-align: left;
        }
        th {
            background-color: #F5F6FA;
            font-weight: bold;
            color: #2C2C2A;
        }
        .text-right {
            text-align: right;
        }
        .text-green {
            color: #3B6D11;
        }
        .text-red {
            color: #A32D2D;
        }
        .text-blue {
            color: #1B4F8A;
        }
        .total-row td {
            font-weight: bold;
            background-color: #F8FAFC;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Laporan Keuangan Joglo66</h1>
        <p>Periode: {{ ucfirst($monthName) }} {{ $year }}</p>
        <p style="font-size: 11px; margin-top: 8px;">Dibuat pada: {{ $generateAt }}</p>
    </div>

    <div class="section">
        <div class="section-title">Ringkasan Keuangan</div>
        <table>
            <tr>
                <td width="70%">Total Pemasukan</td>
                <td width="30%" class="text-right text-green">Rp {{ number_format($total_income, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total Pengeluaran</td>
                <td class="text-right text-red">Rp {{ number_format($total_expense, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td class="text-blue">Laba Bersih</td>
                <td class="text-right text-blue">Rp {{ number_format($net_profit, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Rincian Pemasukan</div>
        <table>
            <tr>
                <th width="70%">Kategori / Jenis Transaksi</th>
                <th width="30%" class="text-right">Nominal</th>
            </tr>
            <tr>
                <td>Penyewaan Lapangan (Total Booking Keseluruhan)</td>
                <td class="text-right">Rp {{ number_format($income['booking'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">- Uang Muka (Down Payment)</td>
                <td class="text-right">Rp {{ number_format($income['down_payment'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">- Pelunasan (Final Payment)</td>
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
        </table>
    </div>

    <div class="section">
        <div class="section-title">Rincian Pengeluaran</div>
        <table>
            <tr>
                <th width="70%">Kategori / Jenis Transaksi</th>
                <th width="30%" class="text-right">Nominal</th>
            </tr>
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
        </table>
    </div>

</body>
</html>
