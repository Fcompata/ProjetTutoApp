<script type="text/javascript">
        var gk_isXlsx = false;
        var gk_xlsxFileLookup = {};
        var gk_fileData = {};
        function filledCell(cell) {
          return cell !== '' && cell != null;
        }
        function loadFileData(filename) {
        if (gk_isXlsx && gk_xlsxFileLookup[filename]) {
            try {
                var workbook = XLSX.read(gk_fileData[filename], { type: 'base64' });
                var firstSheetName = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheetName];

                // Convert sheet to JSON to filter blank rows
                var jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, blankrows: false, defval: '' });
                // Filter out blank rows (rows where all cells are empty, null, or undefined)
                var filteredData = jsonData.filter(row => row.some(filledCell));

                // Heuristic to find the header row by ignoring rows with fewer filled cells than the next row
                var headerRowIndex = filteredData.findIndex((row, index) =>
                  row.filter(filledCell).length >= filteredData[index + 1]?.filter(filledCell).length
                );
                // Fallback
                if (headerRowIndex === -1 || headerRowIndex > 25) {
                  headerRowIndex = 0;
                }

                // Convert filtered JSON back to CSV
                var csv = XLSX.utils.aoa_to_sheet(filteredData.slice(headerRowIndex)); // Create a new sheet from filtered array of arrays
                csv = XLSX.utils.sheet_to_csv(csv, { header: 1 });
                return csv;
            } catch (e) {
                console.error(e);
                return "";
            }
        }
        return gk_fileData[filename] || "";
        }
        </script>
@extends('layouts.app')
<?php
// resources/views/energy-estimation/index.blade.php
?>
@section('title', 'Liste des Estimations d\'Énergie')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-list me-2" style="color: #667eea;"></i>
                Liste des Estimations d'Énergie
            </h1>
            <p class="text-muted mb-0">Gérez vos estimations énergétiques pour vos missions robotiques</p>
        </div>
    </div>
</div>

<!-- Message de succès -->
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-table me-2"></i>
            Estimations
        </h5>
        <div>
            <a href="{{ route('energy-estimation.create') }}" class="btn btn-light btn-sm">
                <i class="fas fa-plus me-1"></i>Nouvelle Estimation
            </a>
            <!-- Uncomment the following line if CSV export is implemented -->
            <!-- <a href="{{ route('energy-estimation.export-csv') }}" class="btn btn-light btn-sm ms-2"><i class="fas fa-file-csv me-1"></i>Exporter en CSV</a> -->
        </div>
    </div>
    <div class="card-body">
        @if ($estimations->isEmpty())
            <div class="text-center py-4">
                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                <p>Aucune estimation trouvée. <a href="{{ route('energy-estimation.create') }}">Créez-en une maintenant</a>.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nom</th>
                            <th>Longueur (m)</th>
                            <th>Vitesse (m/s)</th>
                            <th>Obstacles</th>
                            <th>Capacité Batterie (mWh)</th>
                            <th>Créé le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($estimations as $estimation)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $estimation->name ?: 'Sans nom' }}</td>
                                <td>{{ number_format($estimation->trajectory_length, 2) }}</td>
                                <td>{{ number_format($estimation->average_speed, 2) }}</td>
                                <td>{{ $estimation->obstacle_count }}</td>
                                <td>{{ number_format($estimation->battery_capacity, 2) }}</td>
                                <td>{{ $estimation->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('energy-estimation.show', $estimation) }}"
                                           class="btn btn-outline-primary btn-sm" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('energy-estimation.edit', $estimation) }}"
                                           class="btn btn-outline-warning btn-sm" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('energy-estimation.destroy', $estimation) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Voulez-vous vraiment supprimer cette estimation ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection