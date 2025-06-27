<?php
// resources/views/energy-estimation/show.blade.php
?>
@extends('layouts.app')

@section('title', 'Détails de l\'Estimation d\'Énergie')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-robot me-2" style="color: #667eea;"></i>
                    Détails de l'Estimation d'Énergie
                </h1>
                <p class="text-muted mb-0">
                    {{ $energyEstimation->name ?: 'Estimation sans nom' }} - Créée le {{ $energyEstimation->created_at->format('d/m/Y H:i') }}
                </p>
            </div>
            <div>
                <a href="{{ route('energy-estimation.edit', $energyEstimation) }}" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-edit me-1"></i>Modifier
                </a>
                <!-- Uncomment the following line if CSV export for single estimation is implemented -->
                <!-- <a href="{{ route('energy-estimation.export-csv', $energyEstimation) }}" class="btn btn-outline-success me-2"><i class="fas fa-download me-1"></i>Exporter CSV</a> -->
                <a href="{{ route('energy-estimation.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Résumé Général -->
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Résumé Général
                </h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><strong>Nom :</strong> {{ $energyEstimation->name ?: 'Sans nom' }}</li>
                    <li><strong>Longueur de Trajectoire :</strong> {{ number_format($energyEstimation->trajectory_length, 4) }} m</li>
                    <li><strong>Vitesse Moyenne :</strong> {{ number_format($energyEstimation->average_speed, 4) }} m/s</li>
                    <li><strong>Nombre d'Obstacles :</strong> {{ $energyEstimation->obstacle_count }}</li>
                    <li><strong>Fréquence de Mesure :</strong> {{ $energyEstimation->measurement_frequency }} ms</li>
                    <li><strong>Seuil de Détection :</strong> {{ number_format($energyEstimation->detection_threshold, 4) }} cm</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Consommation Énergétique -->
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Consommation Énergétique
                </h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><strong>Durée Totale :</strong> {{ number_format($results['total_duration'], 4) }} s</li>
                    <li><strong>Temps de Mouvement Avant :</strong> {{ number_format($results['forward_movement_duration'], 4) }} s</li>
                    <li><strong>Temps de Manœuvre :</strong> {{ number_format($results['total_maneuver_time'], 4) }} s</li>
                    <li><strong>Total des Mesures :</strong> {{ round($results['total_measurements']) }}</li>
                    <li><strong>Photos Prises :</strong> {{ $results['photos_taken'] }}</li>
                    <li><strong>Consommation Totale :</strong> {{ number_format($results['total_consumption'], 4) }} mWh</li>
                    <li><strong>Consommation Totale :</strong> {{ number_format($results['total_consumption_wh'], 4) }} Wh</li>
                    @php
                        $percentage = ($results['remaining_autonomy'] / $energyEstimation->battery_capacity) * 100;
                        $badgeClass = $percentage > 50 ? 'bg-success' : ($percentage > 20 ? 'bg-warning' : 'bg-danger');
                    @endphp
                    <li><strong>Autonomie Restante :</strong> <span class="badge {{ $badgeClass }}">{{ number_format($percentage, 1) }}%</span> ({{ number_format($results['remaining_autonomy'], 4) }} mWh)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Répartition de la Consommation -->
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Répartition de la Consommation
                </h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    @foreach ($energyEstimation->components as $component)
                        <li><i class="fas fa-microchip me-2"></i>{{ $component->name }} : {{ number_format($results['components'][$component->id]['consumption'], 4) }} mWh</li>
                    @endforeach
                </ul>
                <canvas id="consumptionChart" class="mt-4"></canvas>
            </div>
        </div>
    </div>

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
                <ul class="list-unstyled">
                    <li><strong>Temps de Recul :</strong> {{ number_format($energyEstimation->backup_time, 4) }} s</li>
                    <li><strong>Temps de Mesure Latérale :</strong> {{ number_format($energyEstimation->lateral_measurement_time, 4) }} s</li>
                    <li><strong>Temps de Rotation :</strong> {{ number_format($energyEstimation->rotation_time, 4) }} s</li>
                </ul>
            </div>
        </div>
    </div>
</div>

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
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('consumptionChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: [@foreach ($energyEstimation->components as $component)'{{ $component->name }}', @endforeach],
                datasets: [{
                    data: [@foreach ($energyEstimation->components as $component){{ $results['components'][$component->id]['consumption'] }}, @endforeach],
                    backgroundColor: ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEEAD', '#D4A5A5', '#D4A017', '#17A2B8', '#6610F2'],
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 20, padding: 15 } },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.label || '';
                                if (label) label += ': ';
                                label += context.raw.toFixed(4) + ' mWh';
                                return label;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endpush