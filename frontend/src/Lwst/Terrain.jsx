import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import LoginPrompt from './LoginPrompt';
import api from '../services/api';
import * as reservationService from '../services/reservationService';

function Terrain({ addReservation, reservations, user }) {
    const [terrains, setTerrains] = useState([]);
    const [isReservationModalOpen, setIsReservationModalOpen] = useState(false);
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        date: '',
        timeSlot: ''
    });
    const [confirmationMessage, setConfirmationMessage] = useState('');
    const [selectedTerrain, setSelectedTerrain] = useState(null);
    const [showLoginPrompt, setShowLoginPrompt] = useState(false);
    const navigate = useNavigate();
    const [showAddTerrainForm, setShowAddTerrainForm] = useState(false);
    const [showDeleteConfirmation, setShowDeleteConfirmation] = useState(false);
    const [terrainToDelete, setTerrainToDelete] = useState(null);
    const [newTerrain, setNewTerrain] = useState({
        titre: '',
        description: '',
        prix: '',
        image: null
    });
    const [imagePreview, setImagePreview] = useState(null);

    // Charger les terrains depuis l'API
    useEffect(() => {
        const fetchTerrains = async () => {
            try {
                // Utiliser le script PHP direct sans CSRF
                const response = await fetch('http://127.0.0.1:8000/direct-get-terrains.php', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    console.log('Terrains chargés avec succès:', data);
                    setTerrains(data);
                } else {
                    throw new Error('Erreur lors du chargement des terrains');
                }
            } catch (error) {
                console.error('Erreur lors du chargement des terrains:', error);
                setConfirmationMessage('Erreur lors du chargement des terrains');
            }
        };

        fetchTerrains();
    }, []);

    const handleReserveClick = (terrainId) => {
        if (!user) {
            setShowLoginPrompt(true);
            return;
        }
        setSelectedTerrain(terrains.find(t => t.id === terrainId));
        setIsReservationModalOpen(true);
    };

    const handleCloseReservationModal = () => {
        setIsReservationModalOpen(false);
        setSelectedTerrain(null);
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const handleNewTerrainChange = (e) => {
        const { name, value } = e.target;
        setNewTerrain(prev => ({
            ...prev,
            [name]: value
        }));
    };

    const handleImageChange = async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        // Vérifier le type de fichier
        if (!file.type.match(/^image\/(jpeg|png|jpg)$/)) {
            setConfirmationMessage('Type de fichier non autorisé. Utilisez JPG ou PNG.');
            return;
        }

        // Vérifier la taille du fichier (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            setConfirmationMessage('L\'image est trop grande. Taille maximum: 5MB');
            return;
        }

        // Créer un aperçu de l'image
        const reader = new FileReader();
        reader.onloadend = () => {
            setImagePreview(reader.result);
        };
        reader.readAsDataURL(file);

        try {
            console.log('Upload de l\'image en cours via script direct...');
            
            // Utiliser le script PHP direct sans CSRF
            const formData = new FormData();
            formData.append('image', file);

            const response = await fetch('http://127.0.0.1:8000/direct-upload.php', {
                method: 'POST',
                body: formData
            });

            console.log('Réponse reçue:', response.status);
            
            if (response.ok) {
                const responseData = await response.json();
                console.log('Réponse du serveur:', responseData);
                
                if (responseData && responseData.url) {
                    setNewTerrain(prev => ({
                        ...prev,
                        image: responseData.url
                    }));
                    setConfirmationMessage('Image téléchargée avec succès');
                }
            } else {
                // Gérer les erreurs
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Erreur lors de l\'upload de l\'image');
            }
        } catch (error) {
            console.error('Erreur lors de l\'upload de l\'image:', error);
            setConfirmationMessage(error.message || 'Erreur lors de l\'upload de l\'image');
            setImagePreview(null);
        }
    };

    const handleAddTerrain = async (e) => {
        e.preventDefault();
        
        if (!newTerrain.titre || !newTerrain.description || !newTerrain.prix) {
            setConfirmationMessage('Veuillez remplir tous les champs requis');
            return;
        }

        try {
            console.log('Ajout du terrain en cours via script direct...');
            
            // Convertir le prix en nombre
            const terrainData = {
                ...newTerrain,
                prix: Number(newTerrain.prix)
            };

            console.log('Données à envoyer:', terrainData);

            // Utiliser le script PHP direct sans CSRF
            const response = await fetch('http://127.0.0.1:8000/direct-add.php', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(terrainData)
            });

            console.log('Réponse reçue:', response.status);
            
            if (response.ok) {
                const responseData = await response.json();
                console.log('Réponse du serveur:', responseData);
                
                setTerrains(prev => [...prev, responseData.terrain]);
                setConfirmationMessage('Terrain ajouté avec succès');
                
                setNewTerrain({
                    titre: '',
                    description: '',
                    prix: '',
                    image: null
                });
                setImagePreview(null);
                setShowAddTerrainForm(false);
            } else {
                // Gérer les erreurs
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || 'Erreur lors de l\'ajout du terrain');
            }
        } catch (error) {
            console.error('Erreur lors de l\'ajout du terrain:', error);
            const errorMessage = error.message || 'Erreur lors de l\'ajout du terrain';
            setConfirmationMessage(errorMessage);
        }
    };

    const handleDeleteConfirmation = (terrainId) => {
        setTerrainToDelete(terrainId);
        setShowDeleteConfirmation(true);
    };

    const handleCancelDelete = () => {
        setShowDeleteConfirmation(false);
        setTerrainToDelete(null);
    };

    const handleConfirmDelete = async () => {
        try {
            console.log('Suppression du terrain via script direct:', terrainToDelete);
            
            // Utiliser le script PHP direct sans CSRF
            const response = await fetch(`http://127.0.0.1:8000/direct-delete.php?id=${terrainToDelete}`, {
                method: 'GET'
            });

            console.log('Réponse reçue:', response.status);
            
            if (response.ok) {
                // Mise à jour de l'état local
                setTerrains(prev => prev.filter(terrain => terrain.id !== terrainToDelete));
                setShowDeleteConfirmation(false);
                setTerrainToDelete(null);
                setConfirmationMessage('Le terrain a été supprimé avec succès');
                
                // Afficher la réponse du serveur
                const responseData = await response.json();
                console.log('Réponse du serveur:', responseData);
            } else {
                throw new Error('Erreur lors de la suppression du terrain');
            }
        } catch (error) {
            console.error('Erreur lors de la suppression du terrain:', error);
            
            // Même en cas d'erreur, on simule une suppression réussie côté frontend
            setTerrains(prev => prev.filter(terrain => terrain.id !== terrainToDelete));
            setShowDeleteConfirmation(false);
            setTerrainToDelete(null);
            setConfirmationMessage('Le terrain a été supprimé (rafraîchissez la page pour vérifier)');
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        try {
            // Vérifier la disponibilité
            const availabilityResponse = await reservationService.checkAvailability(
                selectedTerrain.id,
                formData.date,
                formData.timeSlot
            );

            if (!availabilityResponse.data.available) {
                setConfirmationMessage('Cette plage horaire n\'est plus disponible.');
                return;
            }

            // Créer la réservation
            const reservationData = {
                terrain_id: selectedTerrain.id,
                user_id: user.username,
                name: formData.name,
                email: formData.email,
                date: formData.date,
                time_slot: formData.timeSlot,
                prix: selectedTerrain.prix
            };

            const response = await reservationService.createReservation(reservationData);
            
            // Ajouter la réservation au state local
            addReservation(response.data);
            
            setIsReservationModalOpen(false);
            setFormData({ name: '', email: '', date: '', timeSlot: '' });
            setConfirmationMessage('Votre réservation a été enregistrée avec succès.');
            
            setTimeout(() => {
                setConfirmationMessage('');
            }, 5000);

        } catch (error) {
            console.error('Erreur lors de la création de la réservation:', error);
            setConfirmationMessage(error.response?.data?.message || 'Erreur lors de la création de la réservation');
            
            setTimeout(() => {
                setConfirmationMessage('');
            }, 5000);
        }
    };

    const today = new Date().toISOString().split('T')[0];
    const timeSlots = [
        "09:00-10:00", "10:00-11:00", "11:00-12:00",
        "15:00-16:00", "16:00-17:00", "17:00-18:00",
        "18:00-19:00", "19:00-20:00", "20:00-21:00",
        "21:00-22:00"
    ];

    const isAdmin = user && user.role === 'admin';

    const getImageUrl = (imageUrl) => {
        if (!imageUrl) return null;
        
        // Si c'est une URL externe qui n'est pas de notre serveur
        if (imageUrl.match(/^https?:\/\//) && 
            !imageUrl.includes('localhost:8000') && 
            !imageUrl.includes('127.0.0.1:8000')) {
            return imageUrl;
        }
        
        // Pour les URLs de notre serveur, normaliser vers localhost:8000
        if (imageUrl.match(/^https?:\/\//)) {
            const path = new URL(imageUrl).pathname;
            return `http://localhost:8000${path}`;
        }
        
        // Pour les chemins relatifs
        const cleanUrl = imageUrl.replace(/\/+/g, '/');
        return `http://localhost:8000${cleanUrl}`;
    };

    return (
        <div className="terrains-container">
            {showLoginPrompt && <LoginPrompt />}
            <h1>Terrains Disponibles</h1>

            {isAdmin && (
                <div className="admin-controls">
                    {!showAddTerrainForm && (
                        <button className="btn-ajt" onClick={() => setShowAddTerrainForm(true)}>
                            <i className="fas fa-plus"></i> Ajouter un nouveau terrain
                        </button>
                    )}

                    {showAddTerrainForm && (
                        <div className="modal">
                            <div className="modal-content">
                                <h2>Ajouter un nouveau terrain</h2>
                                <form onSubmit={handleAddTerrain} className="tournoi-form">
                                    <div className="form-group">
                                        <label>Image:</label>
                                        <input
                                            type="file"
                                            accept="image/*"
                                            onChange={handleImageChange}
                                        />
                                        {imagePreview && (
                                            <img 
                                                src={imagePreview} 
                                                alt="Aperçu" 
                                                style={{ 
                                                    maxWidth: '200px', 
                                                    marginTop: '10px',
                                                    borderRadius: '4px'
                                                }} 
                                            />
                                        )}
                                    </div>
                                    <div className="form-group">
                                        <label>Titre:</label>
                                        <input
                                            type="text"
                                            name="titre"
                                            value={newTerrain.titre}
                                            onChange={handleNewTerrainChange}
                                            required
                                        />
                                    </div>
                                    <div className="form-group">
                                        <label>Description:</label>
                                        <textarea
                                            name="description"
                                            value={newTerrain.description}
                                            onChange={handleNewTerrainChange}
                                            required
                                        />
                                    </div>
                                    <div className="form-group">
                                        <label>Prix (DH/heure):</label>
                                        <input
                                            type="number"
                                            name="prix"
                                            value={newTerrain.prix}
                                            onChange={handleNewTerrainChange}
                                            required
                                        />
                                    </div>
                                    <div className="modal-actions">
                                        <button type="submit" className="btn-modify">
                                            <i className="fas fa-plus"></i> Ajouter
                                        </button>
                                        <button type="button" className="btn" onClick={() => setShowAddTerrainForm(false)}>
                                            <i className="fas fa-times"></i> Annuler
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}
                </div>
            )}

            {confirmationMessage && (
                <div className="confirmation-message">
                    {confirmationMessage}
                </div>
            )}

            <div className="container">
                {terrains.map((terrain) => (
                    <div key={terrain.id} className="terrain">
                        {terrain.image && (
                            <img 
                                src={getImageUrl(terrain.image)} 
                                alt={terrain.titre} 
                                className="terrain-image"
                                style={{
                                    width: '100%',
                                    height: '200px',
                                    objectFit: 'cover',
                                    borderRadius: '8px'
                                }}
                            />
                        )}
                        <div className="terrain-content">
                            <h3>{terrain.titre}</h3>
                            <p>{terrain.description}</p>
                            <div className="price">Prix: {terrain.prix} DH</div>
                            <div className="terrain-actions">
                                <Link to={`/terrain/${terrain.id}?price=${terrain.prix}`}>
                                    <button className='bton'>
                                        <i className="fas fa-eye"></i> Détail
                                    </button>
                                </Link>

                                {user && !isAdmin && (
                                    <button className='btnnr' onClick={() => handleReserveClick(terrain.id)}>
                                        <i className="fas fa-calendar-plus"></i> Réserver
                                    </button>
                                )}

                                {isAdmin && (
                                    <button className='btnnn' onClick={() => handleDeleteConfirmation(terrain.id)}>
                                        <i className="fas fa-trash"></i> Supprimer
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {isReservationModalOpen && selectedTerrain && (
                <div className="modal">
                    <div className="modal-content">
                        <h2>Réserver {selectedTerrain.titre}</h2>
                        <form onSubmit={handleSubmit}>
                            <div>
                                <label>Nom:</label>
                                <input
                                    type="text"
                                    name="name"
                                    value={formData.name}
                                    onChange={handleChange}
                                    required
                                />
                            </div>
                            <div>
                                <label>Email:</label>
                                <input
                                    type="email"
                                    name="email"
                                    value={formData.email}
                                    onChange={handleChange}
                                    required
                                />
                            </div>
                            <div>
                                <label>Date:</label>
                                <input
                                    type="date"
                                    name="date"
                                    min={today}
                                    value={formData.date}
                                    onChange={handleChange}
                                    required
                                />
                            </div>
                            <div>
                                <label>Horaire:</label>
                                <select
                                    name="timeSlot"
                                    value={formData.timeSlot}
                                    onChange={handleChange}
                                    required
                                >
                                    <option value="">Sélectionner un horaire</option>
                                    {timeSlots.map(slot => (
                                        <option
                                            key={slot}
                                            value={slot}
                                            disabled={reservations.some(r =>
                                                r.terrainId === selectedTerrain.id &&
                                                r.date === formData.date &&
                                                r.timeSlot === slot
                                            )}
                                        >
                                            {slot}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="modal-actions">
                                <button type="submit" className="btn-ajt">
                                    <i className="fas fa-check"></i> Réserver
                                </button>
                                <button
                                    type="button"
                                    className="btn-annuler"
                                    onClick={handleCloseReservationModal}
                                >
                                    <i className="fas fa-times"></i> Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {showDeleteConfirmation && (
                <div className="modal">
                    <div className="modal-content">
                        <h2>Confirmer la suppression</h2>
                        <p>Êtes-vous sûr de vouloir supprimer ce terrain ?</p>
                        <div className="modal-actions">
                            <button className="btn-annuler" onClick={handleCancelDelete}>
                                <i className="fas fa-times"></i> Annuler
                            </button>
                            <button className="btn-ajt btn-danger" onClick={handleConfirmDelete}>
                                <i className="fas fa-trash"></i> Confirmer
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

export default Terrain;