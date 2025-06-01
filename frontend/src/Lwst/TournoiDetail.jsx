import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import LoginPrompt from './LoginPrompt';
import * as tournamentService from '../services/tournamentService';
import './tournoi.css';

function TournoiDetail({ user }) {
    const { id } = useParams();
    const navigate = useNavigate();
    const [tournoi, setTournoi] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [confirmationMessage, setConfirmationMessage] = useState('');

    useEffect(() => {
        const fetchTournament = async () => {
            try {
                setLoading(true);
                const data = await tournamentService.getTournamentById(id);
                setTournoi(data);
                setLoading(false);
            } catch (error) {
                console.error('Error fetching tournament details:', error);
                setError('Erreur lors du chargement des détails du tournoi');
                setLoading(false);
            }
        };

        fetchTournament();
    }, [id]);

    const handleUnregister = async () => {
        if (!tournoi) return;
        
        console.log('Désinscription initiée dans TournoiDetail');
        
        try {
            // Utiliser la fonction mise à jour qui utilise l'ID utilisateur automatiquement
            const updatedTournament = await tournamentService.unregisterTeam(tournoi.id);
            
            // Mettre à jour les données du tournoi local
            setTournoi(updatedTournament);
            
            // Afficher message de confirmation
            setConfirmationMessage('Vous avez été désinscrit du tournoi avec succès.');
            
            // Masquer le message après 5 secondes
            setTimeout(() => {
                setConfirmationMessage('');
            }, 5000);
        } catch (error) {
            console.error('Erreur lors de la désinscription:', error);
            setError(error.message || 'Une erreur s\'est produite lors de la désinscription.');
            
            setTimeout(() => {
                setError(null);
            }, 5000);
        }
    };

    // Si l'utilisateur n'est pas connecté, afficher l'invite de connexion
    if (!user) {
        return <LoginPrompt />;
    }
    
    // Afficher un message de chargement pendant que les données sont récupérées
    if (loading) {
        return (
            <div className="tournoi-detail-loading">
                <i className="fas fa-spinner fa-pulse"></i>
                <p>Chargement des données du tournoi...</p>
            </div>
        );
    }

    // Afficher un message d'erreur si la récupération a échoué
    if (error) {
        return (
            <div className="tournoi-detail-error">
                <h2>Erreur</h2>
                <p>{error}</p>
                <button onClick={() => navigate('/tournoi')} className="btn-back">
                    <i className="fas fa-arrow-left"></i> Retour aux tournois
                </button>
            </div>
        );
    }
    
    // Si le tournoi n'existe pas, afficher un message d'erreur
    if (!tournoi) {
        return (
            <div className="tournoi-detail-error">
                <h2>Tournoi introuvable</h2>
                <p>Le tournoi demandé n'existe pas ou a été supprimé.</p>
                <button onClick={() => navigate('/tournoi')} className="btn-back">
                    <i className="fas fa-arrow-left"></i> Retour aux tournois
                </button>
            </div>
        );
    }
    
    // Récupérer les équipes inscrites au tournoi
    const teams = tournoi.teams || [];
    const teamsCount = teams.length;
    const placesRestantes = tournoi.max_teams - teamsCount;
    
    // Vérifier si l'utilisateur est déjà inscrit
    const isUserRegistered = (() => {
        if (!user || !teams || teams.length === 0) {
            return false;
        }
        
        for (const team of teams) {
            const emailMatch = team.email === user.email;
            const captainMatch = team.captain === user.username;
            const userIdMatch = team.user_id === user.id;
            
            if (emailMatch || captainMatch || userIdMatch) {
                return true;
            }
        }
        
        return false;
    })();
    
    return (
        <div className="tournoi-detail-page">
            {confirmationMessage && (
                <div className="confirmation-message">
                    {confirmationMessage}
                </div>
            )}
            
            {/* En-tête du tournoi avec titre et boutons d'action */}
            <div className="tournoi-detail-header">
                <h1>{tournoi.name}</h1>
                <div className="tournoi-detail-actions">
                    {isUserRegistered ? (
                        <button 
                            className="btn-unregister"
                            onClick={handleUnregister}
                        >
                            <i className="fas fa-user-minus"></i> Se désinscrire
                        </button>
                    ) : (
                        user.role !== 'admin' && (
                            <button 
                                className="btn-register"
                                onClick={() => navigate(`/tournoi`)}
                                disabled={teamsCount >= tournoi.max_teams}
                            >
                                <i className="fas fa-user-plus"></i> S'inscrire
                            </button>
                        )
                    )}
                    <button onClick={() => navigate('/tournoi')} className="btn-back">
                        <i className="fas fa-arrow-left"></i> Retour
                    </button>
                </div>
            </div>
            
            {/* Contenu principal avec informations et équipes */}
            <div className="tournoi-detail-container">
                <div className="tournoi-info-section">
                    <div className="tournoi-info-card">
                        <h2>Informations</h2>
                        <ul className="tournoi-info-list">
                            <li><strong><i className="fas fa-calendar-alt"></i> Date:</strong> {tournoi.date ? tournoi.date.split('T')[0] : tournoi.date}</li>
                            <li><strong><i className="fas fa-users"></i> Équipes:</strong> {teamsCount}/{tournoi.max_teams} <span className="places-restantes">({placesRestantes} places restantes)</span></li>
                            <li><strong><i className="fas fa-trophy"></i> Récompense:</strong> {tournoi.prize_pool || "Trophée du tournoi"}</li>
                            <li><strong><i className="fas fa-receipt"></i> Frais d'inscription:</strong> {tournoi.entry_fee || "Gratuit"}</li>
                            <li><strong><i className="fas fa-clipboard-list"></i> Format:</strong> {tournoi.format || "Non spécifié"}</li>
                        </ul>
                    </div>
                    
                    <div className="tournoi-description-card">
                        <h2>Description</h2>
                        <p>{tournoi.description || "Aucune description disponible pour ce tournoi."}</p>
                    </div>
                    
                    <div className="tournoi-rules-card">
                        <h2>Règlement</h2>
                        <ul className="tournoi-rules-list">
                            <li><i className="fas fa-check-circle"></i> Chaque équipe doit avoir 5 joueurs minimum</li>
                            <li><i className="fas fa-check-circle"></i> Les matchs durent 2 x 45 minutes</li>
                            <li><i className="fas fa-check-circle"></i> Le format est à élimination directe</li>
                            <li><i className="fas fa-check-circle"></i> En cas d'égalité, tirs au but directs</li>
                            <li><i className="fas fa-check-circle"></i> Présentation 30 minutes avant le début du match</li>
                        </ul>
                    </div>
                </div>
                
                <div className="tournoi-teams-section">
                    <div className="tournoi-teams-header">
                        <h2>Équipes participantes</h2>
                        <div className="tournoi-teams-badge">{teamsCount}/{tournoi.max_teams}</div>
                    </div>
                    
                    {teams.length === 0 ? (
                        <div className="tournoi-no-teams">
                            <i className="fas fa-users-slash"></i>
                            <p>Aucune équipe inscrite pour le moment.</p>
                            {user && !isUserRegistered && tournoi.max_teams > 0 && (
                                <p className="tournoi-no-teams-sub">Soyez la première équipe à vous inscrire!</p>
                            )}
                        </div>
                    ) : (
                        <div className="tournoi-teams-grid">
                            {teams.map(team => (
                                <div className={`tournoi-team-card ${(team.email === user.email || team.captain === user.username || (team.user_id && team.user_id === user.id)) ? 'your-team' : ''}`} key={team.id}>
                                    <div className="tournoi-team-header">
                                        <h3>{team.name}</h3>
                                        {(team.email === user.email || team.captain === user.username || (team.user_id && team.user_id === user.id)) && (
                                            <div className="tournoi-team-tag">Votre équipe</div>
                                        )}
                                    </div>
                                    <div className="tournoi-team-info">
                                        <div className="tournoi-team-item">
                                            <span className="tournoi-team-label"><i className="fas fa-user-tie"></i> Capitaine:</span>
                                            <span className="tournoi-team-value">{team.captain}</span>
                                        </div>
                                        <div className="tournoi-team-item">
                                            <span className="tournoi-team-label"><i className="fas fa-users"></i> Joueurs:</span>
                                            <span className="tournoi-team-value">{team.players || 5}</span>
                                        </div>
                                        <div className="tournoi-team-item">
                                            <span className="tournoi-team-label"><i className="fas fa-calendar-check"></i> Inscription:</span>
                                            <span className="tournoi-team-value">{team.registration_date || "Non spécifiée"}</span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

export default TournoiDetail; 