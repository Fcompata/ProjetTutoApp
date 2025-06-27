<script type="text/javascript">
        import { evaluate } from 'https://cdn.jsdelivr.net/npm/mathjs@11.8.0/lib/browser/math.js';

// Fonction pour évaluer les formules de consommation
export function evaluateComponentConsumption(components, inputs) {
    const results = {
        total_consumption: 0,
        component_details: [],
    };

    components.forEach(comp => {
        let energy = 0;
        try {
            // Remplacer les variables dans la formule
            const scope = {
                value: comp.consumption_rate || 0,
                total_measurements: inputs.total_measurements || 0,
                photos_taken: inputs.photos_taken || 0,
                forward_movement_duration: inputs.forward_movement_duration || 0,
                total_maneuver_time: inputs.total_maneuver_time || 0,
                lateral_measurement_time: inputs.lateral_measurement_time || 0,
                rotation_time: inputs.rotation_time || 0,
                total_duration: inputs.total_duration || 0,
                obstacle_count: inputs.obstacle_count || 0,
            };
            energy = evaluate(comp.formula, scope);
            if (isNaN(energy) || !isFinite(energy)) {
                energy = 0;
            }
        } catch (e) {
            console.error(`Erreur dans la formule pour ${comp.name}:`, e);
            energy = 0;
        }
        results.total_consumption += energy;
        results.component_details.push({
            name: comp.name,
            consumption: energy.toFixed(4),
        });
    });

    return results;
}

// Mettre à jour la prévisualisation
export function updatePreview(previewElement, inputs, components) {
    const results = calculateResults(inputs);
    const consumptionResults = evaluateComponentConsumption(components, results);

    previewElement.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-clock me-2"></i>Durée Totale</h6>
                <p>${results.total_duration.toFixed(2)} secondes</p>
                <h6><i class="fas fa-ruler me-2"></i>Temps de Mouvement Avant</h6>
                <p>${results.forward_movement_duration.toFixed(2)} secondes</p>
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Temps de Manœuvre</h6>
                <p>${results.total_maneuver_time.toFixed(2)} secondes</p>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-broadcast-tower me-2"></i>Total Mesures Ultrasoniques</h6>
                <p>${results.total_measurements} mesures</p>
                <h6><i class="fas fa-camera me-2"></i>Photos Prises</h6>
                <p>${results.photos_taken} photos</p>
                <h6><i class="fas fa-battery-full me-2"></i>Consommation Totale</h6>
                <p>${consumptionResults.total_consumption.toFixed(2)} mWh (${(consumptionResults.total_consumption / 1000).toFixed(4)} Wh)</p>
                <h6><i class="fas fa-battery-half me-2"></i>Autonomie Restante</h6>
                <p>${(inputs.battery_capacity - consumptionResults.total_consumption).toFixed(2)} mWh</p>
                <h6><i class="fas fa-microchip me-2"></i>Consommation par Composant</h6>
                <ul>${consumptionResults.component_details.map(comp => `<li><strong>${comp.name} :</strong> ${comp.consumption} mWh</li>`).join('')}</ul>
            </div>
        </div>
    `;
}

// Calculs des résultats de base
function calculateResults(inputs) {
    const forward_movement_duration = inputs.trajectory_length / inputs.average_speed;
    const maneuver_time_per_obstacle = inputs.backup_time + inputs.lateral_measurement_time + inputs.rotation_time;
    const total_maneuver_time = inputs.obstacle_count * maneuver_time_per_obstacle;
    const total_duration = forward_movement_duration + total_maneuver_time;

    const measurements_during_movement = Math.ceil((total_duration * 1000) / inputs.measurement_frequency);
    const lateral_measurements_per_obstacle = Math.ceil((inputs.lateral_measurement_time * 1000) / inputs.measurement_frequency);
    const total_lateral_measurements = inputs.obstacle_count * lateral_measurements_per_obstacle * 2;
    const total_measurements = measurements_during_movement + total_lateral_measurements;
    const photos_taken = inputs.obstacle_count;

    return {
        forward_movement_duration,
        total_maneuver_time,
        total_duration,
        total_measurements,
        photos_taken,
    };
}
        </script>
@extends('layouts.app')

@section('title', 'Modifier l\'Estimation d\'Énergie')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-edit me-2" style="color: #667eea;"></i>
                Modifier l'Estimation d'Énergie
            </h1>
            <p class="text-muted mb-0">Modifier les paramètres pour l'estimation {{ $energyEstimation->name ?: 'sans nom' }}</p>
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

<form action="{{ route('energy-estimation.update', $energyEstimation) }}" method="POST" id="estimation-form">
    @csrf
    @method('PUT')

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
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                               value="{{ old('name', $energyEstimation->name) }}" placeholder="Ex : Mission Entrepôt A" maxlength="255">
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
                                    <input type="number" step="0.0001" min="0.0001" class="form-control @error('trajectory_length') is-invalid @enderror"
                                           id="trajectory_length" name="trajectory_length"
                                           value="{{ old('trajectory_length', $energyEstimation->trajectory_length) }}" required>
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
                                    <input type="number" step="0.0001" min="0.0001" class="form-control @error('average_speed') is-invalid @enderror"
                                           id="average_speed" name="average_speed"
                                           value="{{ old('average_speed', $energyEstimation->average_speed) }}" required>
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
                                <input type="number" min="0" class="form-control @error('obstacle_count') is-invalid @enderror"
                                       id="obstacle_count" name="obstacle_count"
                                       value="{{ old('obstacle_count', $energyEstimation->obstacle_count) }}" required>
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
                                    <input type="number" step="0.0001" min="0.0001" class="form-control @error('detection_threshold') is-invalid @enderror"
                                           id="detection_threshold" name="detection_threshold"
                                           value="{{ old('detection_threshold', $energyEstimation->detection_threshold) }}" required>
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
                            <input type="number" min="100" class="form-control @error('measurement_frequency') is-invalid @enderror"
                                   id="measurement_frequency" name="measurement_frequency"
                                   value="{{ old('measurement_frequency', $energyEstimation->measurement_frequency) }}" required>
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
                            <input type="number" step="0.0001" min="0.0001" class="form-control @error('backup_time') is-invalid @enderror"
                                   id="backup_time" name="backup_time"
                                   value="{{ old('backup_time', $energyEstimation->backup_time) }}" required>
                            <span class="input-group-text">s</span>
                        </div>
                        <div class="form-text">Durée du recul après détection d'obstacle</div>
                        @error('backup_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </.include>
                        <div class="mb-3">
                            <label for="lateral_measurement_time" class="form-label">
                                <i class="fas fa-arrows-alt-h me-1"></i>Temps de Mesure Latérale *
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0.0001" class="form-control @error('lateral_measurement_time') is-invalid @enderror"
                                       id="lateral_measurement_time" name="lateral_measurement_time"
                                       value="{{ old('lateral_measurement_time', $energyEstimation->lateral_measurement_time) }}" required>
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
                                <input type="number" step="0.0001" min="0.0001" class="form-control @error('rotation_time') is-invalid @enderror"
                                       id="rotation_time" name="rotation_time"
                                       value="{{ old('rotation_time', $energyEstimation->rotation_time) }}" required>
                                <span class="input-group-text">s</span>
                            </div>
                            <div class="form-text">Durée pour le changement de direction</div>
                            @error('rotation_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                 <!-- Consommations des Composants -->
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
                @foreach ([
                    'ultrasonic' => ['name' => 'Capteur Ultrasonique', 'default_unit' => 'mWh par mesure', 'default_formula' => 'value * total_measurements'],
                    'servo' => ['name' => 'Moteur Servo', 'default_unit' => 'mWh par seconde', 'default_formula' => 'value * lateral_measurement_time'],
                    'camera' => ['name' => 'Caméra', 'default_unit' => 'mWh par photo', 'default_formula' => 'value * photos_taken'],
                    'streaming' => ['name' => 'Streaming', 'default_unit' => 'mWh par seconde', 'default_formula' => 'value * total_duration'],
                    'propulsion' => ['name' => 'Moteur de Propulsion', 'default_unit' => 'mWh par seconde', 'default_formula' => 'value * forward_movement_duration'],
                    'microcontroller' => ['name' => 'Microcontrôleur', 'default_unit' => 'mWh par seconde', 'default_formula' => 'value * total_duration'],
                ] as $key => $staticComponent)
                    <div class="component mb-3">
                        <div class="row">
                            <div class="col-md-5">
                                <label class="form-label">Nom du Composant *</label>
                                <input type="text" class="form-control" name="components[static][{{ $key }}][name]"
                                       value="{{ old('components.static.' . $key . '.name', $staticComponent['name']) }}"
                                       readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Consommation *</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0"
                                           class="form-control @error('components.static.' . $key . '.consumption_rate') is-invalid @enderror"
                                           name="components[static][{{ $key }}][consumption_rate]"
                                           value="{{ old('components.static.' . $key . '.consumption_rate', $energyEstimation->components->where('name', $staticComponent['name'])->first()->consumption_rate ?? 0.1) }}"
                                           required>
                                    <select class="form-select" name="components[static][{{ $key }}][unit]" required>
                                        <option value="mWh par mesure" {{ old('components.static.' . $key . '.unit', $energyEstimation->components->where('name', $staticComponent['name'])->first()->unit ?? $staticComponent['default_unit']) == 'mWh par mesure' ? 'selected' : '' }}>mWh par mesure</option>
                                        <option value="mWh par seconde" {{ old('components.static.' . $key . '.unit', $energyEstimation->components->where('name', $staticComponent['name'])->first()->unit ?? $staticComponent['default_unit']) == 'mWh par seconde' ? 'selected' : '' }}>mWh par seconde</option>
                                        <option value="mWh par photo" {{ old('components.static.' . $key . '.unit', $energyEstimation->components->where('name', $staticComponent['name'])->first()->unit ?? $staticComponent['default_unit']) == 'mWh par photo' ? 'selected' : '' }}>mWh par photo</option>
                                    </select>
                                </div>
                                @error('components.static.' . $key . '.consumption_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Formule *</label>
                                <input type="text" class="form-control formula-input"
                                       name="components[static][{{ $key }}][formula]"
                                       value="{{ old('components.static.' . $key . '.formula', $energyEstimation->components->where('name', $staticComponent['name'])->first()->formula ?? $staticComponent['default_formula']) }}"
                                       placeholder="Ex : value * total_measurements" required>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Composants Dynamiques -->
            <div id="dynamic-components-container">
                <h6 class="mb-3">Composants Additionnels</h6>
                @foreach ($energyEstimation->components->whereNotIn('name', ['Capteur Ultrasonique', 'Moteur Servo', 'Caméra', 'Streaming', 'Moteur de Propulsion', 'Microcontrôleur']) as $index => $component)
                    <div class="component mb-3 dynamic-component" data-index="{{ $index }}">
                        <div class="row">
                            <div class="col-md-5">
                                <label class="form-label">Nom du Composant *</label>
                                <input type="text" class="form-control" name="components[dynamic][{{ $index }}][name]"
                                       value="{{ old('components.dynamic.' . $index . '.name', $component->name) }}"
                                       placeholder="Ex : Capteur Additionnel" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Consommation *</label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0"
                                           class="form-control" name="components[dynamic][{{ $index }}][consumption_rate]"
                                           value="{{ old('components.dynamic.' . $index . '.consumption_rate', $component->consumption_rate) }}"
                                           required>
                                    <select class="form-select" name="components[dynamic][{{ $index }}][unit]" required>
                                        <option value="mWh par mesure" {{ old('components.dynamic.' . $index . '.unit', $component->unit) == 'mWh par mesure' ? 'selected' : '' }}>mWh par mesure</option>
                                        <option value="mWh par seconde" {{ old('components.dynamic.' . $index . '.unit', $component->unit) == 'mWh par seconde' ? 'selected' : '' }}>mWh par seconde</option>
                                        <option value="mWh par photo" {{ old('components.dynamic.' . $index . '.unit', $component->unit) == 'mWh par photo' ? 'selected' : '' }}>mWh par photo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Formule *</label>
                                <input type="text" class="form-control formula-input"
                                       name="components[dynamic][{{ $index }}][formula]"
                                       value="{{ old('components.dynamic.' . $index . '.formula', $component->formula) }}"
                                       placeholder="Ex : value * total_measurements" required>
                                <button type="button" class="btn btn-danger btn-sm mt-2 remove-component">Supprimer</button>
                            </div>
                        </div>
                    </div>
                @endforeach
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
                                    <input type="number" step="0.0001" min="0.0001" class="form-control @error('battery_capacity') is-invalid @enderror"
                                           id="battery_capacity" name="battery_capacity"
                                           value="{{ old('battery_capacity', $energyEstimation->battery_capacity) }}" required>
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
                                    <button type="button" class="btn btn-sm btn-outline-light" id="calculate-preview">
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
    @endsection
