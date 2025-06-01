import React, { useState, useEffect } from 'react';
import LoginPrompt from './LoginPrompt';
import { Link } from 'react-router-dom';
import * as tournamentService from '../services/tournamentService';
import './tournoi.css';

function Tournoi({ user }) {
    const [tournaments, setTournaments] = useState([]);
    const [isRegistrationModalOpen, setIsRegistrationModalOpen] = useState(false);
    const [isAddTournoiModalOpen, setIsAddTournoiModalOpen] = useState(false);
    const [isEditMode, setIsEditMode] = useState(false);
    const [selectedTournoi, setSelectedTournoi] = useState(null);
    const [formData, setFormData] = useState({
        teamName: '',
        captainName: '',
        phoneNumber: '',
        email: ''
    });
    const [tournoiFormData, setTournoiFormData] = useState({
        name: '',
        date: '',
        maxTeams: '',
        registeredTeams: 0,
        prizePool: '',
        description: '',
        format: '',
        entryFee: ''
    });
    const [confirmationMessage, setConfirmationMessage] = useState('');
    const [showLoginPrompt, setShowLoginPrompt] = useState(false);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchTournaments();
    }, []);

    useEffect(() => {
        if (user) {
            setFormData(prev => ({
                ...prev,
                email: user.email || '',
                captainName: user.username || ''
            }));
        }
    }, [user]);

    const fetchTournaments = async () => {
        try {
            const data = await tournamentService.getAllTournaments();
            setTournaments(data);
        } catch (error) {
            setError('Erreur lors du chargement des tournois');
            console.error('Error:', error);
        }
    };

    const handleRegisterClick = (tournoi) => {
        if (!user) {
            setShowLoginPrompt(true);
            return;
        }
        setSelectedTournoi(tournoi);
        setIsRegistrationModalOpen(true);
        setFormData(prev => ({
            ...prev, 
            email: user.email || '',
            captainName: user.username || ''
        }));
    };

    const handleCloseModal = () => {
        setIsRegistrationModalOpen(false);
        setSelectedTournoi(null);
        setFormData({
            teamName: '',
            captainName: '',
            phoneNumber: '',
            email: ''
        });
    };

    const handleCloseTournoiModal = () => {
        setIsAddTournoiModalOpen(false);
        setIsEditMode(false);
        setSelectedTournoi(null);
        setTournoiFormData({
            name: '',
            date: '',
            maxTeams: '',
            registeredTeams: 0,
            prizePool: '',
            description: '',
            format: '',
            entryFee: ''
        });
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData({
            ...formData,
            [name]: value
        });
    };

    const handleTournoiChange = (e) => {
        const { name, value } = e.target;
        setTournoiFormData({
            ...tournoiFormData,
            [name]: value
        });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const teamData = {
                team_name: formData.teamName,
                captain_name: formData.captainName,
                phone_number: formData.phoneNumber,
                email: formData.email
            };

            console.log('Registering team with data:', teamData);

            const updatedTournament = await tournamentService.registerTeam(selectedTournoi.id, teamData);
            
            // Mettre à jour ce tournoi spécifique dans la liste des tournois
            setTournaments(tournaments.map(t => 
                t.id === updatedTournament.id ? updatedTournament : t
            ));
            
            setConfirmationMessage('Inscription réussie!');
            setTimeout(() => {
                setConfirmationMessage('');
            }, 3000);
            
            handleCloseModal();
            
            // Actualiser tous les tournois pour s'assurer que les données sont à jour
            fetchTournaments();
        } catch (error) {
            setError('Erreur lors de l\'inscription: ' + (error.message || 'Veuillez réessayer'));
            console.error('Error:', error);
            setTimeout(() => {
                setError(null);
            }, 5000);
        }
    };

    const handleEditClick = (tournoi) => {
        // Formater la date pour enlever la partie heure si elle existe
        let formattedDate = tournoi.date;
        if (formattedDate && formattedDate.includes('T')) {
            formattedDate = formattedDate.split('T')[0];
        }
        
        setTournoiFormData({
            name: tournoi.name,
            date: formattedDate,
            maxTeams: tournoi.max_teams,
            prizePool: tournoi.prize_pool,
            description: tournoi.description,
            format: tournoi.format,
            entryFee: tournoi.entry_fee
        });
        setSelectedTournoi(tournoi);
        setIsEditMode(true);
        setIsAddTournoiModalOpen(true);
    };

    const handleEditTournoi = async (e) => {
        e.preventDefault();
        try {
            const updatedTournament = await tournamentService.updateTournament(selectedTournoi.id, {
                name: tournoiFormData.name,
                date: tournoiFormData.date,
                max_teams: tournoiFormData.maxTeams,
                prize_pool: tournoiFormData.prizePool,
                description: tournoiFormData.description,
                format: tournoiFormData.format,
                entry_fee: tournoiFormData.entryFee
            });

            setTournaments(tournaments.map(t => 
                t.id === updatedTournament.id ? updatedTournament : t
            ));
            
            setConfirmationMessage('Tournoi modifié avec succès!');
            setTimeout(() => {
                setConfirmationMessage('');
            }, 3000);
            
            handleCloseTournoiModal();
        } catch (error) {
            setError('Erreur lors de la modification du tournoi');
            console.error('Error:', error);
        }
    };

    const handleAddTournoi = async (e) => {
        e.preventDefault();
        try {
            const newTournament = await tournamentService.createTournament({
                name: tournoiFormData.name,
                date: tournoiFormData.date,
                max_teams: tournoiFormData.maxTeams,
                prize_pool: tournoiFormData.prizePool,
                description: tournoiFormData.description,
                format: tournoiFormData.format,
                entry_fee: tournoiFormData.entryFee,
                registered_teams: 0,
                teams: []
            });

            setTournaments([...tournaments, newTournament]);
            
            setConfirmationMessage('Tournoi ajouté avec succès!');
            setTimeout(() => {
                setConfirmationMessage('');
            }, 3000);
            
            handleCloseTournoiModal();
        } catch (error) {
            setError('Erreur lors de la création du tournoi');
            console.error('Error:', error);
        }
    };

    const handleDeleteTournoi = async (id) => {
        try {
            await tournamentService.deleteTournament(id);
            setTournaments(tournaments.filter(t => t.id !== id));
            
            setConfirmationMessage('Tournoi supprimé avec succès!');
            setTimeout(() => {
                setConfirmationMessage('');
            }, 3000);
        } catch (error) {
            setError('Erreur lors de la suppression du tournoi');
            console.error('Error:', error);
        }
    };

    const isUserRegisteredForTournament = (tournoi) => {
        if (!user || !tournoi || !tournoi.teams) {
            console.log('Conditions préalables non remplies:', {
                userExists: !!user,
                tournamentExists: !!tournoi,
                teamsExist: tournoi ? !!tournoi.teams : false
            });
            return false;
        }
        
        // Log des données pour le débogage
        console.log('Vérification d\'inscription:', {
            tournamentId: tournoi.id,
            tournamentName: tournoi.name,
            userEmail: user.email,
            username: user.username,
            userId: user.id,
            teamsCount: tournoi.teams.length
        });
        
        // Vérifier les correspondances possible entre l'utilisateur et les équipes inscrites
        for (const team of tournoi.teams) {
            console.log('Équipe examinée:', team);
            
            const emailMatch = team.email === user.email;
            const captainMatch = team.captain === user.username;
            const userIdMatch = team.user_id === user.id; // Attention: dans les teams c'est user_id, pas userId
            
            console.log('Comparaison avec l\'utilisateur:', {
                équipe: team.name,
                teamId: team.id,
                teamEmail: team.email,
                teamCaptain: team.captain,
                teamUserId: team.user_id,
                userEmail: user.email,
                userUsername: user.username,
                userId: user.id,
                emailMatch,
                captainMatch,
                userIdMatch
            });
            
            if (emailMatch || captainMatch || userIdMatch) {
                console.log('CORRESPONDANCE TROUVÉE ✅ - Utilisateur inscrit dans l\'équipe:', team.name, 'avec ID:', team.id);
                // Stocker l'ID de l'équipe trouvée pour pouvoir le réutiliser lors de la désinscription
                tournoi._foundTeamId = team.id;
                return true;
            }
        }
        
        console.log('Aucune correspondance trouvée ❌ - Utilisateur NON inscrit au tournoi', tournoi.id);
        return false;
    };

    const handleUnregister = async (tournamentId, teamId = null) => {
        try {
            console.log('Désinscription du tournoi:', tournamentId);
            
            // Trouver le tournoi concerné pour récupérer l'ID d'équipe stocké par isUserRegisteredForTournament
            const tournament = tournaments.find(t => t.id === tournamentId);
            const foundTeamId = tournament ? tournament._foundTeamId : null;
            
            console.log('Tentative de désinscription avec ID équipe trouvé:', foundTeamId);
            
            // Appeler le service avec l'ID d'équipe trouvé si disponible
            const updatedTournament = await tournamentService.unregisterTeam(tournamentId, foundTeamId);
            
            console.log('Désinscription réussie:', updatedTournament);
            
            // Mettre à jour l'état local avec la liste mise à jour des tournois
            setTournaments(prev => 
                prev.map(t => 
                    t.id === tournamentId ? updatedTournament : t
                )
            );
            
            // Afficher un message de confirmation
            setConfirmationMessage('Votre équipe a été désinscrite avec succès.');
            
            // Masquer le message après 5 secondes
            setTimeout(() => {
                setConfirmationMessage('');
            }, 5000);
            
        } catch (error) {
            console.error('Erreur lors de la désinscription:', error);
            setError(error.message || 'Une erreur est survenue lors de la désinscription.');
            
            // Masquer le message d'erreur après 5 secondes
            setTimeout(() => {
                setError(null);
            }, 5000);
        }
    };

    return (
        <div className="tournoi-container">
            {showLoginPrompt && <LoginPrompt />}
            
            {/* En-tête des tournois avec bouton d'ajout pour admin */}
            <div className="tournoi-header">
                <h1>Tournois Disponibles</h1>
                {user && user.role === 'admin' && (
                    <button
                        className="btn-ajt"
                        onClick={() => setIsAddTournoiModalOpen(true)}
                    >
                        <i className="fas fa-plus"></i> Ajouter un tournoi
                    </button>
                )}
            </div>

            {/* Messages d'erreur et de confirmation */}
            {error && <div className="error-message">{error}</div>}
            {confirmationMessage && (
                <div className="confirmation-message">
                    {confirmationMessage}
                </div>
            )}

            {/* Grille de tournois */}
            <div className="tournois-grid">
                {tournaments.map((tournoi) => (
                    <div key={tournoi.id} className="tournoi-card">
                        {user && user.role === 'admin' && (
                            <div className="admin-actions">
                                <button
                                    className="btn-modify"
                                    onClick={() => handleEditClick(tournoi)}
                                    title="Modifier"
                                >
                                    <i className="fas fa-edit"></i>
                                    <span>Modifier</span>
                                </button>
                                <button
                                    className="btn-delete"
                                    onClick={() => handleDeleteTournoi(tournoi.id)}
                                    title="Supprimer"
                                >
                                    <i className="fas fa-trash-alt"></i>
                                    <span>Supprimer</span>
                                </button>
                            </div>
                        )}
                        <h2 className="tournoi-title">{tournoi.name}</h2>
                        <div className="tournoi-info">
                            <p><i className="fas fa-calendar-alt"></i> Date: {tournoi.date ? tournoi.date.split('T')[0] : tournoi.date}</p>
                            <p><i className="fas fa-users"></i> Équipes: {tournoi.registered_teams}/{tournoi.max_teams}</p>
                            <p><i className="fas fa-trophy"></i> <strong>Prix:</strong> {tournoi.prize_pool}</p>
                            <p><i className="fas fa-sitemap"></i> <strong>Format:</strong> {tournoi.format}</p>
                            <p><i className="fas fa-money-bill-wave"></i> <strong>Frais d'inscription:</strong> {tournoi.entry_fee}</p>
                            <p className="description">{tournoi.description}</p>
                        </div>

                        <div className="tournoi-actions">
                            {user ? (
                                <>
                                    {user.role !== 'admin' && (
                                        <>
                                            {isUserRegisteredForTournament(tournoi) ? (
                                                <button
                                                    className="btn-unregister"
                                                    onClick={() => handleUnregister(tournoi.id)}
                                                >
                                                    <i className="fas fa-user-minus"></i> Se désinscrire
                                                </button>
                                            ) : (
                                                <button
                                                    className="btn-register"
                                                    onClick={() => handleRegisterClick(tournoi)}
                                                    disabled={tournoi.registered_teams >= tournoi.max_teams}
                                                >
                                                    <i className="fas fa-user-plus"></i> S'inscrire
                                                </button>
                                            )}
                                        </>
                                    )}
                                    <Link to={`/tournoi/${tournoi.id}`} className="btn-view-details">
                                        <i className="fas fa-info-circle"></i> Voir les détails
                                    </Link>
                                </>
                            ) : (
                                <>
                                    <div className="login-required">
                                        <i className="fas fa-lock"></i> Connectez-vous pour participer à ce tournoi.
                                    </div>
                                    <Link to={`/tournoi/${tournoi.id}`} className="btn-view-details">
                                        <i className="fas fa-info-circle"></i> Voir les détails
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                ))}
            </div>

            {/* Modals pour inscription, ajout et édition de tournoi */}
            {isRegistrationModalOpen && selectedTournoi && (
                <div className="modal">
                    <div className="modal-content">
                        <h2>Inscription au {selectedTournoi.name}</h2>
                        <form onSubmit={handleSubmit} className="registration-form">
                            <div className="form-group">
                                <label>Nom de l'équipe:</label>
                                <input
                                    type="text"
                                    name="teamName"
                                    value={formData.teamName}
                                    onChange={handleChange}
                                    required
                                    placeholder="Entrez le nom de votre équipe"
                                />
                            </div>
                            <div className="form-group">
                                <label>Nom du capitaine:</label>
                                <input
                                    type="text"
                                    name="captainName"
                                    value={formData.captainName}
                                    onChange={handleChange}
                                    required
                                    placeholder="Nom du capitaine"
                                />
                            </div>
                            <div className="form-group">
                                <label>Numéro de téléphone:</label>
                                <input
                                    type="tel"
                                    name="phoneNumber"
                                    value={formData.phoneNumber}
                                    onChange={handleChange}
                                    required
                                    placeholder="0600000000"
                                />
                            </div>
                            <div className="form-group">
                                <label>Email:</label>
                                <input
                                    type="email"
                                    name="email"
                                    value={formData.email}
                                    onChange={handleChange}
                                    required
                                    placeholder="exemple@email.com"
                                />
                            </div>
                            <div className="modal-actions">
                                <button type="submit" className="btn-ajt">
                                    <i className="fas fa-check-circle"></i> S'inscrire
                                </button>
                                <button type="button" className="btn-annuler" onClick={handleCloseModal}>
                                    <i className="fas fa-times-circle"></i> Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {isAddTournoiModalOpen && (
                <div className="modal">
                    <div className="modal-content">
                        <h2>{isEditMode ? 'Modifier le tournoi' : 'Ajouter un nouveau tournoi'}</h2>
                        <form onSubmit={isEditMode ? handleEditTournoi : handleAddTournoi} className="tournoi-form">
                            <div className="form-group">
                                <label>Nom du tournoi:</label>
                                <input
                                    type="text"
                                    name="name"
                                    value={tournoiFormData.name}
                                    onChange={handleTournoiChange}
                                    required
                                    placeholder="Nom du tournoi"
                                />
                            </div>
                            <div className="form-group">
                                <label>Date:</label>
                                <input
                                    type="date"
                                    name="date"
                                    value={tournoiFormData.date}
                                    onChange={handleTournoiChange}
                                    required
                                    min={new Date().toISOString().split('T')[0]}
                                />
                            </div>
                            <div className="form-group">
                                <label>Nombre maximum d'équipes:</label>
                                <select
                                    name="maxTeams"
                                    value={tournoiFormData.maxTeams}
                                    onChange={handleTournoiChange}
                                    required
                                    className="form-select"
                                >
                                    <option value="" disabled>Choisir le nombre d'équipes</option>
                                    <option value="8">8 équipes</option>
                                    <option value="12">12 équipes</option>
                                    <option value="16">16 équipes</option>
                                    <option value="20">20 équipes</option>                                
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Prix (Prize Pool):</label>
                                <select
                                    name="prizePool"
                                    value={tournoiFormData.prizePool}
                                    onChange={handleTournoiChange}
                                    required
                                    className="form-select"
                                >
                                    <option value="" disabled>Choisir le prize pool</option>
                                    <option value="1000 DH">1000 DH</option>
                                    <option value="2000 DH">2000 DH</option>
                                    <option value="3000 DH">3000 DH</option>
                                    <option value="4000 DH">4000 DH</option>
                                    <option value="5000 DH">5000 DH</option>
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Format:</label>
                                <select
                                    name="format"
                                    value={tournoiFormData.format}
                                    onChange={handleTournoiChange}
                                    required
                                    className="form-select"
                                >
                                    <option value="" disabled>Choisir un format</option>
                                    <option value="Élimination directe">Élimination directe</option>
                                    <option value="Phase de groupes + Élimination directe">Phase de groupes + Élimination directe</option>
                                    <option value="Championnat">Championnat (matchs aller-retour)</option>
                                    <option value="Coupe">Format Coupe</option>
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Frais d'inscription:</label>
                                <select
                                    name="entryFee"
                                    value={tournoiFormData.entryFee}
                                    onChange={handleTournoiChange}
                                    required
                                    className="form-select"
                                >
                                    <option value="" disabled>Choisir les frais d'inscription</option>
                                    <option value="300 DH">300 DH</option>
                                    <option value="500 DH">500 DH</option>
                                    <option value="600 DH">600 DH</option>
                                    <option value="700 DH">700 DH</option>
                                    <option value="800 DH">800 DH</option>
                                </select>
                            </div>
                            <div className="form-group">
                                <label>Description:</label>
                                <textarea
                                    name="description"
                                    value={tournoiFormData.description}
                                    onChange={handleTournoiChange}
                                    required
                                    placeholder="Description du tournoi"
                                />
                            </div>
                            <div className="modal-actions">
                                <button type="submit" className="btn-modify">
                                    <i className="fas fa-save"></i> {isEditMode ? 'Enregistrer' : 'Ajouter'}
                                </button>
                                <button type="button" className="btn" onClick={handleCloseTournoiModal}>
                                    <i className="fas fa-times-circle"></i> Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

export default Tournoi;