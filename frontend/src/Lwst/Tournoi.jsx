import React, { useState, useEffect } from 'react';
import LoginPrompt from './LoginPrompt';
import { Link } from 'react-router-dom';

function Tournoi({ user, tournois = [], setTournois }) {
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
    const [registeredTeams, setRegisteredTeams] = useState(() => {
        const saved = localStorage.getItem('registeredTeams');
        return saved ? JSON.parse(saved) : {};
    });
    const [showLoginPrompt, setShowLoginPrompt] = useState(false);
    const [deleteConfirmationId, setDeleteConfirmationId] = useState(null);

    useEffect(() => {
        localStorage.setItem('registeredTeams', JSON.stringify(registeredTeams));
    }, [registeredTeams]);

    const handleRegisterClick = (tournoi) => {
        if (!user) {
            setShowLoginPrompt(true);
            return;
        }
        setSelectedTournoi(tournoi);
        setIsRegistrationModalOpen(true);
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

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Enregistrer l'équipe pour ce tournoi
        setRegisteredTeams(prev => ({
            ...prev,
            [selectedTournoi.id]: formData
        }));
        
        // Mettre à jour le tournoi
        setTournois(prevTournois => 
            prevTournois.map(tournoi => 
                tournoi.id === selectedTournoi.id 
                    ? { 
                        ...tournoi, 
                        registered: true,
                        registeredTeams: (tournoi.registeredTeams || 0) + 1,
                        teams: [
                            ...(tournoi.teams || []),
                            {
                                id: Date.now(),
                                name: formData.teamName,
                                captain: formData.captainName,
                                players: 5,
                                registrationDate: new Date().toISOString().split('T')[0]
                            }
                        ]
                    } 
                    : tournoi
            )
        );
        
        setConfirmationMessage('Inscription réussie!');
        setTimeout(() => {
            setConfirmationMessage('');
        }, 3000);
        
        setIsRegistrationModalOpen(false);
        setFormData({
            teamName: '',
            captainName: '',
            phoneNumber: '',
            email: ''
        });
    };

    const handleEditClick = (tournoi) => {
        setTournoiFormData({ ...tournoi });
        setSelectedTournoi(tournoi);
        setIsEditMode(true);
        setIsAddTournoiModalOpen(true);
    };

    const handleEditTournoi = (e) => {
        e.preventDefault();

        setTournois(prevTournois => 
            prevTournois.map(tournoi => 
                tournoi.id === selectedTournoi.id ? { ...tournoiFormData } : tournoi
            )
        );

        setConfirmationMessage('Tournoi modifié avec succès!');
        setTimeout(() => {
            setConfirmationMessage('');
        }, 3000);
        
        setIsAddTournoiModalOpen(false);
        setIsEditMode(false);
        setSelectedTournoi(null);
    };

    const handleAddTournoi = (e) => {
        e.preventDefault();

        const newTournoi = {
            id: Date.now(),
            ...tournoiFormData,
            teams: []
        };

        setTournois(prevTournois => [...prevTournois, newTournoi]);
        
        setConfirmationMessage('Tournoi ajouté avec succès!');
        setTimeout(() => {
            setConfirmationMessage('');
        }, 3000);
        
        setIsAddTournoiModalOpen(false);
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

    const handleDeleteTournoi = (id) => {
        setTournois(prevTournois => 
            prevTournois.filter(tournoi => tournoi.id !== id)
        );
        
        setConfirmationMessage('Tournoi supprimé avec succès!');
        setTimeout(() => {
            setConfirmationMessage('');
        }, 3000);
        
        setDeleteConfirmationId(null);
    };

    const handleUnregister = (tournoiId) => {
        // Supprimer l'inscription
        const updatedRegisteredTeams = { ...registeredTeams };
        delete updatedRegisteredTeams[tournoiId];
        setRegisteredTeams(updatedRegisteredTeams);
        
        // Trouver le tournoi
        const tournoi = tournois.find(t => t.id === tournoiId);
        if (!tournoi) return;
        
        // Trouver l'équipe à supprimer (basée sur l'utilisateur)
        const userTeam = tournoi.teams?.find(team => 
            user && (team.email === user.email || team.captain === user.username)
        );
        
        if (!userTeam) {
            // Si l'équipe n'est pas trouvée, simplement mettre à jour le statut
            setTournois(prevTournois => 
                prevTournois.map(t => 
                    t.id === tournoiId 
                        ? { 
                            ...t, 
                            registered: false,
                            registeredTeams: Math.max(0, t.registeredTeams - 1) 
                        } 
                        : t
                )
            );
        } else {
            // Supprimer l'équipe du tableau des équipes
            setTournois(prevTournois => 
                prevTournois.map(t => 
                    t.id === tournoiId 
                        ? { 
                            ...t, 
                            registered: false,
                            registeredTeams: Math.max(0, t.registeredTeams - 1),
                            teams: t.teams?.filter(team => team.id !== userTeam.id) || []
                        } 
                        : t
                )
            );
        }
        
        setConfirmationMessage('Désinscription réussie!');
        setTimeout(() => {
            setConfirmationMessage('');
        }, 3000);
    };

    return (
        <div className="tournoi-container">
            {showLoginPrompt && <LoginPrompt />}
            <div className="tournoi-header">
                <h1>Tournois Disponibles</h1>
                {user && user.role === 'admin' && (
                    <button
                        className="btn-ajt"
                        onClick={() => setIsAddTournoiModalOpen(true)}
                    >
                        <i className="fas fa-plus"></i> Ajouter un nouveau tournoi
                    </button>
                )}
            </div>

            <div className="tournois-grid">
                {tournois && tournois.map((tournoi) => (
                    <div key={tournoi.id} className="tournoi-card">
                        <h2 className="tournoi-title">{tournoi.name}</h2>
                        <div className="tournoi-info">
                            <p><i className="fas fa-calendar-alt"></i> Date: {tournoi.date}</p>
                            <p><i className="fas fa-users"></i> Équipes: {tournoi.teams ? tournoi.teams.length : 0}/{tournoi.maxTeams}</p>
                            <p><strong>Prix:</strong> {tournoi.prizePool}</p>
                            <p><strong>Format:</strong> {tournoi.format}</p>
                            <p><strong>Frais d'inscription:</strong> {tournoi.entryFee}</p>
                            <p className="description">{tournoi.description}</p>
                        </div>

                        {user ? (
                            <div className="tournoi-actions">
                                {user.role !== 'admin' && (
                                    <>
                                        {tournoi.registered ? (
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
                                                disabled={tournoi.teams && tournoi.teams.length >= tournoi.maxTeams}
                                            >
                                                <i className="fas fa-user-plus"></i> S'inscrire
                                            </button>
                                        )}
                                    </>
                                )}
                              <Link to={`/tournoi/${tournoi.id}`} className="btn btn-view-details">
    <i className="fas fa-info-circle"></i> Voir les détails
</Link>

{user.role === 'admin' && (
    <div className="admin-actions">
        <button
            className="btn btn-modify"
            onClick={() => handleEditClick(tournoi)}
        >
            <i className="fas fa-edit"></i> Modifier
        </button>
        <button
            className="btn btn-delete"
            onClick={() => handleDeleteTournoi(tournoi.id)}
        >
            <i className="fas fa-trash-alt"></i> Supprimer
        </button>
    </div>
)}

                            </div>
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
                ))}
            </div>

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
                                <button type="submit" className="btn-ajt">S'inscrire</button>
                                <button type="button" className="btn-annuler" onClick={handleCloseModal}>Annuler</button>
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
                                    {isEditMode ? 'Modifier' : 'Ajouter'}
                                </button>
                                <button type="button" className="btn" onClick={handleCloseTournoiModal}>Annuler</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {confirmationMessage && (
                <div className="confirmation-message">
                    {confirmationMessage}
                </div>
            )}
        </div>
    );
}

export default Tournoi;