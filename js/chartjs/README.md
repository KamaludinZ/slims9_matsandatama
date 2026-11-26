# Chart.js

Dipakai SLiMS untuk grafik dashboard admin dan laporan.

- `chart-2.9.4.min.js` – Chart.js v2.9.4 (https://www.chartjs.org), lisensi MIT (lihat LICENSE.md).
  Diambil dari paket npm `chart.js@2.9.4` (`dist/Chart.min.js`).

Chart.js 1.0.1-beta.4 yang lama sudah dihapus karena celah keamanan CVE-2020-7746
(prototype pollution, diperbaiki mulai 2.9.4). Pemakainya (admin/default/home.php dan
admin/modules/reporting/pop_chart.php) sudah dipindahkan ke API Chart.js 2.
