import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import LoginPrompt from './LoginPrompt';

function TournoiDetail({ user, tournois, setTournois }) {
    const { id } = useParams();
    const navigate = useNavigate();
    const [tournoi, setTournoi] = useState(null);
    const [loading, setLoading] = useState(true);
    const [confirmationMessage, setConfirmationMessage] = useState('');
    
    useEffect(() => {
        if (tournois && tournois.length > 0) {
            const currentTournoi = tournois.find(t => t.id === parseInt(id) || t.id === id);
            if (currentTournoi) {
                setTournoi(currentTournoi);
            }
            setLoading(false);
        }
    }, [id, tournois]);

    const handleUnregister = () => {
        if (!tournoi || !tournoi.teams) return;
        
        console.log('Désinscription initiée');
        console.log('Utilisateur:', user);
        console.log('Équipes du tournoi:', tournoi.teams);
        
        // Trouver l'équipe de l'utilisateur
        const userTeam = tournoi.teams.find(team => 
            user && (team.email === user.email || team.captain === user.username || (team.userId && team.userId === user.id))
        );
        
        console.log('Équipe trouvée:', userTeam);
        
        if (!userTeam) {
            console.log('Aucune équipe trouvée pour cet utilisateur');
            setConfirmationMessage('Erreur: Votre équipe n\'a pas été trouvée.');
            setTimeout(() => {
                setConfirmationMessage('');
            }, 5000);
            return;
        }
        
        // Filtrer pour enlever l'équipe
        const updatedTeams = tournoi.teams.filter(team => team.id !== userTeam.id);
        console.log('Équipes après filtrage:', updatedTeams);
        
        // Mettre à jour le tournoi
        const updatedTournoi = {
            ...tournoi,
            teams: updatedTeams,
            registered: false
        };
        
        console.log('Tournoi mis à jour:', updatedTournoi);
        
        // Mettre à jour la liste des tournois
        const updatedTournois = tournois.map(t => {
            if (t.id === tournoi.id) {
                return updatedTournoi;
            }
            return t;
        });
        
        // Mettre à jour l'état global des tournois
        setTournois(updatedTournois);
        console.log('État global des tournois mis à jour');
        
        // Mettre à jour l'état local
        setTournoi(updatedTournoi);
        console.log('État local du tournoi mis à jour');
        
        setConfirmationMessage('Votre équipe a été désinscrite du tournoi avec succès.');
        setTimeout(() => {
            setConfirmationMessage('');
        }, 5000);
        
        // Rediriger vers la liste des tournois après désinscription
        setTimeout(() => {
            console.log('Redirection vers la liste des tournois');
            navigate('/tournoi');
        }, 2000);
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
    
    // Si le tournoi n'existe pas, afficher un message d'erreur
    if (!tournoi) {
        return (
            <div className="tournoi-detail-error">
                <h2>Tournoi introuvable</h2>
                <p>Le tournoi demandé n'existe pas ou a été supprimé.</p>
                <button onClick={() => navigate('/tournois')} className="btn-back">
                    <i className="fas fa-arrow-left"></i> Retour aux tournois
                </button>
            </div>
        );
    }
    
    // Récupérer les équipes inscrites au tournoi
    const teams = tournoi.teams || [];
    const teamsCount = teams.length;
    const placesRestantes = tournoi.maxTeams - teamsCount;
    
    // Vérifier si l'utilisateur est déjà inscrit
    const isUserRegistered = tournoi.registered || (tournoi.teams && tournoi.teams.some(team => 
        user && (team.email === user.email || team.captain === user.username || (team.userId && team.userId === user.id))
    ));
    
    return (
        <div className="tournoi-detail-container">
            {confirmationMessage && (
                <div className="confirmation-message">
                    <i className="fas fa-check-circle"></i> {confirmationMessage}
                </div>
            )}
            
            <div className="tournoi-detail-header">
                <button onClick={() => navigate('/tournoi')} className="btn-back">
                    <i className="fas fa-arrow-left"></i> Retour
                </button>
                <h1>{tournoi.name}</h1>
            </div>
            
            <div className="tournoi-detail-info">
                <div className="tournoi-detail-card">
                    <h2>Informations du tournoi</h2>
                    <div className="info-item">
                        <span className="info-label"><i className="fas fa-calendar-alt"></i> Date:</span>
                        <span className="info-value">{tournoi.date}</span>
                    </div>
                    <div className="info-item">
                        <span className="info-label"><i className="fas fa-users"></i> Équipes:</span>
                        <span className="info-value">{teamsCount}/{tournoi.maxTeams} ({placesRestantes} places restantes)</span>
                    </div>
                    <div className="info-item">
                        <span className="info-label"><i className="fas fa-trophy"></i> Récompense:</span>
                        <span className="info-value">{tournoi.prizePool || "Trophée du tournoi"}</span>
                    </div>
                    <div className="info-item">
                        <span className="info-label"><i className="fas fa-receipt"></i> Frais d'inscription:</span>
                        <span className="info-value">{tournoi.entryFee || "Gratuit"}</span>
                    </div>
                    <div className="info-item">
                        <span className="info-label"><i className="fas fa-clipboard-list"></i> Format:</span>
                        <span className="info-value">{tournoi.format || "Non spécifié"}</span>
                    </div>
                    <div className="info-item">
                        <span className="info-label"><i className="fas fa-info-circle"></i> Description:</span>
                        <span className="info-value description">{tournoi.description}</span>
                    </div>
                </div>
                
                <div className="rules-card">
                    <h2>Règles du tournoi</h2>
                    <ul className="rules-list">
                        <li><i className="fas fa-check-circle"></i> Chaque équipe doit avoir 5 joueurs minimum</li>
                        <li><i className="fas fa-check-circle"></i> Les matchs durent 2 x 45 minutes</li>
                        <li><i className="fas fa-check-circle"></i> Le format est à élimination directe</li>
                        <li><i className="fas fa-check-circle"></i> En cas d'égalité, tirs au but directs</li>
                        <li><i className="fas fa-check-circle"></i> Présentation 30 minutes avant le début du match</li>
                    </ul>
                </div>
            </div>
            
            <div className="teams-section">
                <h2>Équipes participantes ({teamsCount}/{tournoi.maxTeams})</h2>
                {teams.length === 0 ? (
                    <div className="no-teams">
                        <i className="fas fa-users-slash"></i>
                        <p>Aucune équipe inscrite pour le moment.</p>
                        {user && !isUserRegistered && (
                            <p className="no-teams-sub">Soyez la première équipe à vous inscrire!</p>
                        )}
                    </div>
                ) : (
                    <div className="teams-grid">
                        {teams.map(team => (
                            <div className="team-card" key={team.id}>
                                <div className="team-header">
                                    <h3>{team.name}</h3>
                                    <span className="team-badge">
                                        <i className="fas fa-users"></i> {team.players || 5} joueurs
                                    </span>
                                </div>
                                <div className="team-info">
                                    <div className="team-info-item">
                                        <span className="team-info-label">Capitaine:</span>
                                        <span className="team-info-value">{team.captain}</span>
                                    </div>
                                    <div className="team-info-item">
                                        <span className="team-info-label">Inscription:</span>
                                        <span className="team-info-value">{team.registrationDate}</span>
                                    </div>
                                    {user && (team.email === user.email || team.captain === user.username || (team.userId && team.userId === user.id)) && (
                                        <div className="team-info-badge">Votre équipe</div>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
                
                {isUserRegistered ? (
                    <div className="registration-info">
                        <div className="registration-status">
                            <i className="fas fa-check-circle"></i>
                            <p>Vous êtes inscrit à ce tournoi</p>
                        </div>
                        
                        <button 
                            className="btn-unregister-tournoi"
                            onClick={handleUnregister}
                        >
                            <i className="fas fa-user-minus"></i> Se désinscrire du tournoi
                        </button>
                    </div>
                ) : (
                    <div className="register-section">
                        {teamsCount < tournoi.maxTeams ? (
                            <>
                                <p>Pour participer à ce tournoi, veuillez retourner à la liste des tournois et cliquer sur "S'inscrire".</p>
                                <Link to="/tournoi" className="Retour">
                                    <i className="fas fa-arrow-left"></i> Retour à la liste des tournois
                                </Link>
                            </>
                        ) : (
                            <p className="tournament-full">
                                <i className="fas fa-exclamation-circle"></i> 
                                Ce tournoi est complet. Les inscriptions sont fermées.
                            </p>
                        )}
                    </div>
                )}
            </div>
            
            {tournoi.bracket && (
                <div className="bracket-section">
                    <h2>Tableau du tournoi</h2>
                    <div className="bracket-placeholder">
                        <i className="fas fa-sitemap"></i>
                        <p>Le tableau du tournoi sera disponible une fois le tournoi commencé.</p>
                    </div>
                </div>
            )}
        </div>
    );
}

export default TournoiDetail; 