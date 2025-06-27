<?php
// resources/views/energy-estimation/create.blade.php
?>
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
                var jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, blankrows: false, defval: '' });
                var filteredData = jsonData.filter(row => row.some(filledCell));
                var headerRowIndex = filteredData.findIndex((row, index) =>
                    row.filter(filledCell).length >= filteredData[index + 1]?.filter(filledCell).length
                );
                if (headerRowIndex === -1 || headerRowIndex > 25) {
                    headerRowIndex = 0;
                }
                var csv = XLSX.utils.aoa_to_sheet(filteredData.slice(headerRowIndex));
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

@section('title', 'Nouvelle Estimation d\'Énergie')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-plus-circle me-2" style="color: #667eea;"></i>
                Nouvelle Estimation d'Énergie
            </h1>
            <p class="text-muted mb-0">Configurez les paramètres de votre robot pour estimer la consommation énergétique</p>
        </div>
    </div>
</div>

<!-- Erreurs de validation -->
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Erreurs de validation :</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Message de succès -->
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('energy-estimation.store') }}" method="POST" id="estimation-form">
    @csrf

    <div class="row">
        <!-- Informations Générales -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Informations Générales
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            <i class="fas fa-tag me-1"></i>Nom de l'Estimation
                        </label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name') }}"
                               placeholder="Ex : Mission Entrepôt A" maxlength="255">
                        <div class="form-text">Facultatif - pour identifier cette estimation</div>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Paramètres de Trajectoire -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-route me-2"></i>
                        Paramètres de Trajectoire
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="trajectory_length" class="form-label">
                                    <i class="fas fa-ruler me-1"></i>Longueur de Trajectoire *
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0.0001"
                                           class="form-control @error('trajectory_length') is-invalid @enderror"
                                           id="trajectory_length" name="trajectory_length"
                                           value="{{ old('trajectory_length', 10) }}" required>
                                    <span class="input-group-text">m</span>
                                </div>
                                @error('trajectory_length')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="average_speed" class="form-label">
                                    <i class="fas fa-tachometer-alt me-1"></i>Vitesse Moyenne *
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0.0001"
                                           class="form-control @error('average_speed') is-invalid @enderror"
                                           id="average_speed" name="average_speed"
                                           value="{{ old('average_speed', 0.5) }}" required>
                                    <span class="input-group-text">m/s</span>
                                </div>
                                @error('average_speed')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="duration-estimate" class="form-text"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="obstacle_count" class="form-label">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Nombre d'Obstacles *
                                </label>
                                <input type="number" min="0"
                                       class="form-control @error('obstacle_count') is-invalid @enderror"
                                       id="obstacle_count" name="obstacle_count"
                                       value="{{ old('obstacle_count', 3) }}" required>
                                @error('obstacle_count')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="detection_threshold" class="form-label">
                                    <i class="fas fa-crosshairs me-1"></i>Seuil de Détection *
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0.0001"
                                           class="form-control @error('detection_threshold') is-invalid @enderror"
                                           id="detection_threshold" name="detection_threshold"
                                           value="{{ old('detection_threshold', 20) }}" required>
                                    <span class="input-group-text">cm</span>
                                </div>
                                @error('detection_threshold')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="measurement_frequency" class="form-label">
                            <i class="fas fa-clock me-1"></i>Fréquence de Mesure *
                        </label>
                        <div class="input-group">
                            <input type="number" min="100"
                                   class="form-control @error('measurement_frequency') is-invalid @enderror"
                                   id="measurement_frequency" name="measurement_frequency"
                                   value="{{ old('measurement_frequency', 500) }}" required>
                            <span class="input-group-text">ms</span>
                        </div>
                        <div class="form-text">Intervalle entre les mesures ultrasoniques</div>
                        @error('measurement_frequency')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Paramètres Temporels -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-stopwatch me-2"></i>
                        Paramètres Temporels
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="backup_time" class="form-label">
                            <i class="fas fa-undo me-1"></i>Temps de Recul *
                        </label>
                        <div class="input-group">
                            <input type="number" step="0.0001" min="0.0001"
                                   class="form-control @error('backup_time') is-invalid @enderror"
                                   id="backup_time" name="backup_time"
                                   value="{{ old('backup_time', 1.0) }}" required>
                            <span class="input-group-text">s</span>
                        </div>
                        <div class="form-text">Durée du recul après détection d'obstacle</div>
                        @error('backup_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="lateral_measurement_time" class="form-label">
                            <i class="fas fa-arrows-alt-h me-1"></i>Temps de Mesure Latérale *
                        </label>
                        <div class="input-group">
                            <input type="number" step="0.0001" min="0.0001"
                                   class="form-control @error('lateral_measurement_time') is-invalid @enderror"
                                   id="lateral_measurement_time" name="lateral_measurement_time"
                                   value="{{ old('lateral_measurement_time', 2.0) }}" required>
                            <span class="input-group-text">s</span>
                        </div>
                        <div class="form-text">Temps pour les mesures gauche/droite</div>
                        @error('lateral_measurement_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="rotation_time" class="form-label">
                            <i class="fas fa-sync-alt me-1"></i>Temps de Rotation *
                        </label>
                        <div class="input-group">
                            <input type="number" step="0.0001" min="0.0001"
                                   class="form-control @error('rotation_time') is-invalid @enderror"
                                   id="rotation_time" name="rotation_time"
                                   value="{{ old('rotation_time', 1.5) }}" required>
                            <span class="input-group-text">s</span>
                        </div>
                        <div class="form-text">Durée pour le changement de direction</div>
                        @error('rotation_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Consommations des Composants -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-microchip me-2"></i>
                        Consommations des Composants
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Composants Statiques -->
                    <div class="mb-4">
                        <h6 class="mb-3">Composants Principaux</h6>
                        <!-- Capteur Ultrasonique -->
                        <div class="component mb-3">
                            <div class="row">
                                <div class="col-md-5">
                                    <label class="form-label">Nom du Composant *</label>
                                    <input type="text" class="form-control" name="components[static][ultrasonic][name]"
                                           value="{{ old('components.static.ultrasonic.name', 'Capteur Ultrasonique') }}"
                                           readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Consommation *</label>
                                    <div class="input-group">
                                        <input type="number" step="0.0001" min="0"
                                               class="form-control @error('components.static.ultrasonic.consumption_rate') is-invalid @enderror"
                                               name="components[static][ultrasonic][consumption_rate]"
                                               value="{{ old('components.static.ultrasonic.consumption_rate', 0.1) }}" required>
                                        <select class="form-select" name="components[static][ultrasonic][unit]" required>
                                            <option value="mWh par mesure" selected>mWh par mesure</option>
                                            <option value="mWh par seconde">mWh par seconde</option>
                                            <option value="mWh par photo">mWh par photo</option>
                                        </select>
                                    </div>
                                    @error('components.static.ultrasonic.consumption_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Formule *</label>
                                    <input type="text" class="form-control formula-input"
                                           name="components[static][ultrasonic][formula]"
                                           value="{{ old('components.static.ultrasonic.formula', 'value * total_measurements') }}"
                                           placeholder="Ex : value * total_measurements" required>
                                </div>
                            </div>
                        </div>
                        <!-- Moteur Servo -->
                        <div class="component mb-3">
                            <div class="row">
                                <div class="col-md-5">
                                    <label class="form-label">Nom du Composant *</label>
                                    <input type="text" class="form-control" name="components[static][servo][name]"
                                           value="{{ old('components.static.servo.name', 'Moteur Servo') }}"
                                           readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Consommation *</label>
                                    <div class="input-group">
                                        <input type="number" step="0.0001" min="0"
                                               class="form-control @error('components.static.servo.consumption_rate') is-invalid @enderror"
                                               name="components[static][servo][consumption_rate]"
                                               value="{{ old('components.static.servo.consumption_rate', 0.5) }}" required>
                                        <select class="form-select" name="components[static][servo][unit]" required>
                                            <option value="mWh par seconde" selected>mWh par seconde</option>
                                            <option value="mWh par mesure">mWh par mesure</option>
                                            <option value="mWh par photo">mWh par photo</option>
                                        </select>
                                    </div>
                                    @error('components.static.servo.consumption_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Formule *</label>
                                    <input type="text" class="form-control formula-input"
                                           name="components[static][servo][formula]"
                                           value="{{ old('components.static.servo.formula', 'value * lateral_measurement_time') }}"
                                           placeholder="Ex : value * lateral_measurement_time" required>
                                </div>
                            </div>
                        </div>
                        <!-- Caméra -->
                        <div class="component mb-3">
                            <div class="row">
                                <div class="col-md-5">
                                    <label class="form-label">Nom du Composant *</label>
                                    <input type="text" class="form-control" name="components[static][camera][name]"
                                           value="{{ old('components.static.camera.name', 'Caméra') }}"
                                           readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Consommation *</label>
                                    <div class="input-group">
                                        <input type="number" step="0.0001" min="0"
                                               class="form-control @error('components.static.camera.consumption_rate') is-invalid @enderror"
                                               name="components[static][camera][consumption_rate]"
                                               value="{{ old('components.static.camera.consumption_rate', 1.0) }}" required>
                                        <select class="form-select" name="components[static][camera][unit]" required>
                                            <option value="mWh par photo" selected>mWh par photo</option>
                                            <option value="mWh par mesure">mWh par mesure</option>
                                            <option value="mWh par seconde">mWh par seconde</option>
                                        </select>
                                    </div>
                                    @error('components.static.camera.consumption_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Formule *</label>
                                    <input type="text" class="form-control formula-input"
                                           name="components[static][camera][formula]"
                                           value="{{ old('components.static.camera.formula', 'value * photos_taken') }}"
                                           placeholder="Ex : value * photos_taken" required>
                                </div>
                            </div>
                        </div>
                        <!-- Streaming -->
                        <div class="component mb-3">
                            <div class="row">
                                <div class="col-md-5">
                                    <label class="form-label">Nom du Composant *</label>
                                    <input type="text" class="form-control" name="components[static][streaming][name]"
                                           value="{{ old('components.static.streaming.name', 'Streaming') }}"
                                           readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Consommation *</label>
                                    <div class="input-group">
                                        <input type="number" step="0.0001" min="0"
                                               class="form-control @error('components.static.streaming.consumption_rate') is-invalid @enderror"
                                               name="components[static][streaming][consumption_rate]"
                                               value="{{ old('components.static.streaming.consumption_rate', 2.0) }}" required>
                                        <select class="form-select" name="components[static][streaming][unit]" required>
                                            <option value="mWh par seconde" selected>mWh par seconde</option>
                                            <option value="mWh par mesure">mWh par mesure</option>
                                            <option value="mWh par photo">mWh par photo</option>
                                        </select>
                                    </div>
                                    @error('components.static.streaming.consumption_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Formule *</label>
                                    <input type="text" class="form-control formula-input"
                                           name="components[static][streaming][formula]"
                                           value="{{ old('components.static.streaming.formula', 'value * total_duration') }}"
                                           placeholder="Ex : value * total_duration" required>
                                </div>
                            </div>
                        </div>
                        <!-- Moteur de Propulsion -->
                        <div class="component mb-3">
                            <div class="row">
                                <div class="col-md-5">
                                    <label class="form-label">Nom du Composant *</label>
                                    <input type="text" class="form-control" name="components[static][propulsion][name]"
                                           value="{{ old('components.static.propulsion.name', 'Moteur de Propulsion') }}"
                                           readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Consommation *</label>
                                    <div class="input-group">
                                        <input type="number" step="0.0001" min="0"
                                               class="form-control @error('components.static.propulsion.consumption_rate') is-invalid @enderror"
                                               name="components[static][propulsion][consumption_rate]"
                                               value="{{ old('components.static.propulsion.consumption_rate', 5.0) }}" required>
                                        <select class="form-select" name="components[static][propulsion][unit]" required>
                                            <option value="mWh par seconde" selected>mWh par seconde</option>
                                            <option value="mWh par mesure">mWh par mesure</option>
                                            <option value="mWh par photo">mWh par photo</option>
                                        </select>
                                    </div>
                                    @error('components.static.propulsion.consumption_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Formule *</label>
                                    <input type="text" class="form-control formula-input"
                                           name="components[static][propulsion][formula]"
                                           value="{{ old('components.static.propulsion.formula', 'value * forward_movement_duration') }}"
                                           placeholder="Ex : value * forward_movement_duration" required>
                                </div>
                            </div>
                        </div>
                        <!-- Microcontrôleur -->
                        <div class="component mb-3">
                            <div class="row">
                                <div class="col-md-5">
                                    <label class="form-label">Nom du Composant *</label>
                                    <input type="text" class="form-control" name="components[static][microcontroller][name]"
                                           value="{{ old('components.static.microcontroller.name', 'Microcontrôleur') }}"
                                           readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Consommation *</label>
                                    <div class="input-group">
                                        <input type="number" step="0.0001" min="0"
                                               class="form-control @error('components.static.microcontroller.consumption_rate') is-invalid @enderror"
                                               name="components[static][microcontroller][consumption_rate]"
                                               value="{{ old('components.static.microcontroller.consumption_rate', 0.2) }}" required>
                                        <select class="form-select" name="components[static][microcontroller][unit]" required>
                                            <option value="mWh par seconde" selected>mWh par seconde</option>
                                            <option value="mWh par mesure">mWh par mesure</option>
                                            <option value="mWh par photo">mWh par photo</option>
                                        </select>
                                    </div>
                                    @error('components.static.microcontroller.consumption_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Formule *</label>
                                    <input type="text" class="form-control formula-input"
                                           name="components[static][microcontroller][formula]"
                                           value="{{ old('components.static.microcontroller.formula', 'value * total_duration') }}"
                                           placeholder="Ex : value * total_duration" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Composants Dynamiques -->
                    <div id="dynamic-components-container">
                        <h6 class="mb-3">Composants Additionnels</h6>
                        <!-- Exemple de composant dynamique initial (facultatif) -->
                        <div class="component mb-3 dynamic-component" data-index="0">
                            <div class="row">
                                <div class="col-md-5">
                                    <label class="form-label">Nom du Composant *</label>
                                    <input type="text" class="form-control" name="components[dynamic][0][name]"
                                           value="{{ old('components.dynamic.0.name') }}"
                                           placeholder="Ex : Capteur Additionnel" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Consommation *</label>
                                    <div class="input-group">
                                        <input type="number" step="0.0001" min="0"
                                               class="form-control" name="components[dynamic][0][consumption_rate]"
                                               value="{{ old('components.dynamic.0.consumption_rate', 0.1) }}" required>
                                        <select class="form-select" name="components[dynamic][0][unit]" required>
                                            <option value="mWh par mesure">mWh par mesure</option>
                                            <option value="mWh par seconde">mWh par seconde</option>
                                            <option value="mWh par photo">mWh par photo</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Formule *</label>
                                    <input type="text" class="form-control formula-input"
                                           name="components[dynamic][0][formula]"
                                           value="{{ old('components.dynamic.0.formula', 'value * total_measurements') }}"
                                           placeholder="Ex : value * total_measurements" required>
                                    <button type="button" class="btn btn-danger btn-sm mt-2 remove-component">Supprimer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary" id="add-component">
                        <i class="fas fa-plus me-1"></i>Ajouter un Composant
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Capacité de la Batterie -->
    <div class="row">
        <div class="col-lg-6 mx-auto">
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-battery-full me-2"></i>
                        Capacité de la Batterie
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="battery_capacity" class="form-label">
                            <i class="fas fa-battery-three-quarters me-1"></i>Capacité Totale *
                        </label>
                        <div class="input-group">
                            <input type="number" step="0.0001" min="0.0001"
                                   class="form-control @error('battery_capacity') is-invalid @enderror"
                                   id="battery_capacity" name="battery_capacity"
                                   value="{{ old('battery_capacity', 2000) }}" required>
                            <span class="input-group-text">mWh</span>
                        </div>
                        <div class="form-text">Capacité nominale de la batterie du robot</div>
                        @error('battery_capacity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Aperçu en Temps Réel -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-eye me-2"></i>
                        Aperçu des Résultats
                        <small class="float-end">
                            <button type="button" class="btn btn-outline-light" id="calculate-preview">
                                <i class="fas fa-calculator me-1"></i>Calculer
                            </button>
                        </small>
                    </h5>
                </div>
                <div class="card-body" id="preview-results">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-calculator fa-3x mb-3"></i>
                        <p>Cliquez sur "Calculer" pour voir un aperçu des résultats</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Boutons d'Action -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <button type="submit" class="btn btn-primary btn-lg me-3" id="submit-btn">
                        <i class="fas fa-save me-2"></i>Enregistrer l'Estimation
                    </button>
                    <a href="{{ route('energy-estimation.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-times me-2"></i>Annuler
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modal d'Aide -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalLabel">
                    <i class="fas fa-question-circle me-2"></i>
                    Guide de l'Utilisateur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="accordion" id="helpAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingRobot">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRobot" aria-expanded="true" aria-controls="collapseRobot">
                                <i class="fas fa-robot me-2"></i>Fonctionnement du Robot
                            </button>
                        </h2>
                        <div id="collapseRobot" class="accordion-collapse collapse show" aria-labelledby="headingRobot" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                <p>Le robot navigue en effectuant des mesures ultrasoniques régulières. Lorsqu'un obstacle est détecté :</p>
                                <ol>
                                    <li><strong>Arrêt et Prise de Photo</strong> - Documenter l'obstacle</li>
                                    <li><strong>Recul</strong> - Pendant la durée définie pour éviter une collision</li>
                                    <li><strong>Mesures Latérales</strong> - Scanner à gauche/droite avec le moteur servo</li>
                                    <li><strong>Rotation</strong> - Tourner dans la direction la plus dégagée</li>
                                    <li><strong>Reprise de la Trajectoire</strong> - Poursuivre la mission</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingCalculations">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCalculations" aria-expanded="false" aria-controls="collapseCalculations">
                                <i class="fas fa-calculator me-2"></i>Calculs Effectués
                            </button>
                        </h2>
                        <div id="collapseCalculations" class="accordion-collapse collapse" aria-labelledby="headingCalculations" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                <ul>
                                    <li><strong>Durée Totale :</strong> Longueur ÷ Vitesse + temps de manœuvre</li>
                                    <li><strong>Mesures Ultrasoniques :</strong> Durée ÷ Fréquence + mesures latérales</li>
                                    <li><strong>Consommation :</strong> Temps d'utilisation × Consommation unitaire par composant</li>
                                    <li><strong>Autonomie Restante :</strong> Capacité de la batterie - Consommation totale</li>
                                    <li><strong>Consommation en Wh :</strong> Consommation totale en mWh ÷ 1000</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOptimization">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOptimization" aria-expanded="false" aria-controls="collapseOptimization">
                                <i class="fas fa-chart-line me-2"></i>Conseils d'Optimisation
                            </button>
                        </h2>
                        <div id="collapseOptimization" class="accordion-collapse collapse" aria-labelledby="headingOptimization" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                <h6>Pour réduire la consommation :</h6>
                                <ul>
                                    <li>Augmenter la fréquence de mesure (moins de mesures par seconde)</li>
                                    <li>Réduire le nombre d'obstacles attendu</li>
                                    <li>Optimiser les temps de manœuvre</li>
                                    <li>Utiliser une vitesse constante appropriée</li>
                                    <li>Minimiser la consommation de streaming si possible</li>
                                </ul>
                                <h6>Pour améliorer la précision :</h6>
                                <ul>
                                    <li>Réduire la fréquence de mesure (plus de mesures)</li>
                                    <li>Ajuster le seuil de détection en fonction de l'environnement</li>
                                    <li>Calibrer les temps de manœuvre pour le robot</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'Enregistrement -->
<div class="modal fade" id="saveModal" tabindex="-1" aria-labelledby="saveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saveModalLabel">
                    <i class="fas fa-save me-2"></i>
                    Enregistrement en Cours
                </h5>
            </div>
            <div class="modal-body text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Enregistrement...</span>
                </div>
                <p>Enregistrement de votre estimation...</p>
            </div>
        </div>
    </div>
</div>

<!-- Bouton d'Aide Flottant -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1030;">
    <button type="button" class="btn btn-info btn-lg rounded-circle shadow"
            data-bs-toggle="modal" data-bs-target="#helpModal" title="Aide">
        <i class="fas fa-question"></i>
    </button>
</div>

<!-- Bouton de Retour en Haut -->
<div class="position-fixed bottom-0 start-0 p-3" style="z-index: 1030;">
    <button type="button" class="btn btn-secondary btn-lg rounded-circle shadow"
            id="scroll-to-top" title="Retour en Haut" style="display: none;">
        <i class="fas fa-chevron-up"></i>
    </button>
</div>

@section('scripts')
    <script type="module">
        import { evaluateComponentConsumption, updatePreview } from '{{ asset('js/formula-evaluator.js') }}';

        let dynamicComponentIndex = {{ old('components.dynamic') ? count(old('components.dynamic')) : 1 }};

        // Ajouter un nouveau composant dynamique
        document.getElementById('add-component').addEventListener('click', function () {
            const container = document.getElementById('dynamic-components-container');
            const newComponent = document.createElement('div');
            newComponent.classList.add('component', 'mb-3', 'dynamic-component');
            newComponent.dataset.index = dynamicComponentIndex;
            newComponent.innerHTML = `
                <div class="row">
                    <div class="col-md-5">
                        <label class="form-label">Nom du Composant *</label>
                        <input type="text" class="form-control" name="components[dynamic][${dynamicComponentIndex}][name]"
                               placeholder="Ex : Capteur Additionnel" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Consommation *</label>
                        <div class="input-group">
                            <input type="number" step="0.0001" min="0" class="form-control"
                                   name="components[dynamic][${dynamicComponentIndex}][consumption_rate]" value="0.1" required>
                            <select class="form-select" name="components[dynamic][${dynamicComponentIndex}][unit]" required>
                                <option value="mWh par mesure">mWh par mesure</option>
                                <option value="mWh par seconde">mWh par seconde</option>
                                <option value="mWh par photo">mWh par photo</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Formule *</label>
                        <input type="text" class="form-control formula-input"
                               name="components[dynamic][${dynamicComponentIndex}][formula]"
                               value="value * total_measurements" placeholder="Ex : value * total_measurements" required>
                        <button type="button" class="btn btn-danger btn-sm mt-2 remove-component">Supprimer</button>
                    </div>
                </div>
            `;
            container.appendChild(newComponent);
            dynamicComponentIndex++;
        });

        // Supprimer un composant dynamique
        document.getElementById('dynamic-components-container').addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-component')) {
                e.target.closest('.dynamic-component').remove();
            }
        });

        // Prévisualisation des calculs
        document.getElementById('calculate-preview').addEventListener('click', function () {
            // Récupérer les valeurs du formulaire
            const inputs = {
                trajectory_length: parseFloat(document.getElementById('trajectory_length').value) || 0,
                average_speed: parseFloat(document.getElementById('average_speed').value) || 0,
                obstacle_count: parseInt(document.getElementById('obstacle_count').value) || 0,
                detection_threshold: parseFloat(document.getElementById('detection_threshold').value) || 0,
                measurement_frequency: parseInt(document.getElementById('measurement_frequency').value) || 0,
                backup_time: parseFloat(document.getElementById('backup_time').value) || 0,
                lateral_measurement_time: parseFloat(document.getElementById('lateral_measurement_time').value) || 0,
                rotation_time: parseFloat(document.getElementById('rotation_time').value) || 0,
                battery_capacity: parseFloat(document.getElementById('battery_capacity').value) || 0,
            };

            // Vérifier les valeurs nécessaires
            if (inputs.trajectory_length <= 0 || inputs.average_speed <= 0 || inputs.measurement_frequency < 100 || inputs.battery_capacity <= 0) {
                document.getElementById('preview-results').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Veuillez entrer des valeurs valides pour la longueur de trajectoire, la vitesse moyenne, la fréquence de mesure et la capacité de la batterie.
                    </div>
                `;
                return;
            }

            // Récupérer les composants statiques
            const staticComponents = [
                {
                    name: document.querySelector('input[name="components[static][ultrasonic][name]"]').value,
                    consumption_rate: parseFloat(document.querySelector('input[name="components[static][ultrasonic][consumption_rate]"]').value) || 0,
                    unit: document.querySelector('select[name="components[static][ultrasonic][unit]"]').value,
                    formula: document.querySelector('input[name="components[static][ultrasonic][formula]"]').value,
                },
                {
                    name: document.querySelector('input[name="components[static][servo][name]"]').value,
                    consumption_rate: parseFloat(document.querySelector('input[name="components[static][servo][consumption_rate]"]').value) || 0,
                    unit: document.querySelector('select[name="components[static][servo][unit]"]').value,
                    formula: document.querySelector('input[name="components[static][servo][formula]"]').value,
                },
                {
                    name: document.querySelector('input[name="components[static][camera][name]"]').value,
                    consumption_rate: parseFloat(document.querySelector('input[name="components[static][camera][consumption_rate]"]').value) || 0,
                    unit: document.querySelector('select[name="components[static][camera][unit]"]').value,
                    formula: document.querySelector('input[name="components[static][camera][formula]"]').value,
                },
                {
                    name: document.querySelector('input[name="components[static][streaming][name]"]').value,
                    consumption_rate: parseFloat(document.querySelector('input[name="components[static][streaming][consumption_rate]"]').value) || 0,
                    unit: document.querySelector('select[name="components[static][streaming][unit]"]').value,
                    formula: document.querySelector('input[name="components[static][streaming][formula]"]').value,
                },
                {
                    name: document.querySelector('input[name="components[static][propulsion][name]"]').value,
                    consumption_rate: parseFloat(document.querySelector('input[name="components[static][propulsion][consumption_rate]"]').value) || 0,
                    unit: document.querySelector('select[name="components[static][propulsion][unit]"]').value,
                    formula: document.querySelector('input[name="components[static][propulsion][formula]"]').value,
                },
                {
                    name: document.querySelector('input[name="components[static][microcontroller][name]"]').value,
                    consumption_rate: parseFloat(document.querySelector('input[name="components[static][microcontroller][consumption_rate]"]').value) || 0,
                    unit: document.querySelector('select[name="components[static][microcontroller][unit]"]').value,
                    formula: document.querySelector('input[name="components[static][microcontroller][formula]"]').value,
                },
            ];

            // Récupérer les composants dynamiques
            const dynamicComponents = Array.from(document.querySelectorAll('.dynamic-component')).map((comp, index) => ({
                name: comp.querySelector(`input[name="components[dynamic][${index}][name]"]`).value,
                consumption_rate: parseFloat(comp.querySelector(`input[name="components[dynamic][${index}][consumption_rate]"]`).value) || 0,
                unit: comp.querySelector(`select[name="components[dynamic][${index}][unit]"]`).value,
                formula: comp.querySelector(`input[name="components[dynamic][${index}][formula]"]`).value,
            }));

            // Combiner les composants
            const allComponents = [...staticComponents, ...dynamicComponents];

            // Mettre à jour la prévisualisation
            updatePreview(document.getElementById('preview-results'), inputs, allComponents);
        });

        // Mettre à jour l'estimation de la durée en temps réel
        function updateDurationEstimate() {
            const length = parseFloat(document.getElementById('trajectory_length').value) || 0;
            const speed = parseFloat(document.getElementById('average_speed').value) || 0;
            const durationElement = document.getElementById('duration-estimate');
            if (length > 0 && speed > 0) {
                const duration = length / speed;
                durationElement.textContent = `Durée estimée : ${duration.toFixed(2)} secondes`;
            } else {
                durationElement.textContent = '';
            }
        }

        document.getElementById('trajectory_length').addEventListener('input', updateDurationEstimate);
        document.getElementById('average_speed').addEventListener('input', updateDurationEstimate);

        // Afficher le bouton "Retour en haut" lors du défilement
        window.addEventListener('scroll', function () {
            const scrollToTop = document.getElementById('scroll-to-top');
            if (window.scrollY > 300) {
                scrollToTop.style.display = 'block';
            } else {
                scrollToTop.style.display = 'none';
            }
        });

        // Gérer le clic sur "Retour en haut"
        document.getElementById('scroll-to-top').addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
@endsection