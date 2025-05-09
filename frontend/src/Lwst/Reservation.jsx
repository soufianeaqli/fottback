import React, { useState, useEffect } from 'react';
import LoginPrompt from './LoginPrompt';
import * as reservationService from '../services/reservationService';

function Reservation({ user }) {
    const [reservations, setReservations] = useState([]);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);
    const [formData, setFormData] = useState({
        id: null,
        name: '',
        date: '',
        timeSlot: '',
        accepted: false,
        rejected: false,
        userId: ''
    });
    const [confirmationMessage, setConfirmationMessage] = useState('');
    const [reservationToDelete, setReservationToDelete] = useState(null);
    const [deletionMessage, setDeletionMessage] = useState('');
    const [paymentForm, setPaymentForm] = useState({
        cardNumber: '',
        cardName: '',
        expiryDate: '',
        cvv: '',
        amount: '',
        reservationId: ''
    });
    
    // Nouveau filtre pour les administrateurs
    const [paymentFilter, setPaymentFilter] = useState('all');
    
    // Initialiser le statut de paiement basé sur les données de réservation
    const initPaymentStatus = () => {
        const status = {};
        reservations.forEach(reservation => {
            if (reservation.isPaid) {
                status[reservation.id] = 'success';
            }
        });
        return status;
    };
    
    const [paymentStatus, setPaymentStatus] = useState(initPaymentStatus);
    
    // Mettre à jour le statut de paiement lorsque les réservations changent
    useEffect(() => {
        setPaymentStatus(initPaymentStatus);
    }, [reservations]);

    // Charger les réservations
    useEffect(() => {
        const fetchReservations = async () => {
            try {
                let response;
                console.log('Chargement des réservations pour:', user.role === 'admin' ? 'admin' : user.username);
                
                if (user.role === 'admin') {
                    response = await reservationService.getAllReservations();
                } else {
                    response = await reservationService.getUserReservations(user.username);
                }
                console.log('Réservations chargées:', response.data);
                setReservations(response.data);
            } catch (error) {
                console.error('Erreur lors du chargement des réservations:', error);
                setConfirmationMessage('Erreur lors du chargement des réservations');
            }
        };

        if (user) {
            fetchReservations();
        }
    }, [user]);

    // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
    if (!user) {
        return <LoginPrompt />;
    }

    // Filtrer les réservations en fonction du rôle de l'utilisateur et du filtre de paiement
    let displayedReservations = user.role === 'admin'
        ? reservations  // Afficher toutes les réservations pour l'admin
        : reservations.filter(res => res.userId === user.username);  // Filtrer pour l'utilisateur standard
    
    // Appliquer le filtre de paiement pour les administrateurs
    if (user.role === 'admin' && paymentFilter !== 'all') {
        displayedReservations = displayedReservations.filter(res => {
            const isPaid = paymentStatus[res.id] === 'success' || res.isPaid;
            return paymentFilter === 'paid' ? isPaid : !isPaid;
        });
    }

    // Calculer le nombre de jours restants pour chaque réservation
    const calculateDaysRemaining = (reservationDate) => {
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Normaliser l'heure à minuit
        const matchDate = new Date(reservationDate);
        matchDate.setHours(0, 0, 0, 0); // Normaliser l'heure à minuit
        
        // Différence en millisecondes convertie en jours
        const diffTime = matchDate.getTime() - today.getTime();
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        return diffDays;
    };

    const handleModifyClick = (reservation) => {
        setFormData({ ...reservation });
        setIsEditModalOpen(true);
    };

    const handleDeleteClick = (reservationId) => {
        setReservationToDelete(reservationId);
        setIsDeleteModalOpen(true);
    };

    const confirmDelete = async () => {
        try {
            console.log('Suppression de la réservation:', reservationToDelete);
            
            // Passer le rôle administrateur au service de suppression
            await reservationService.deleteReservation(
                reservationToDelete, 
                user.username, 
                user.role === 'admin'
            );
            
            // Mettre à jour la liste des réservations
            setReservations(prevReservations => 
                prevReservations.filter(res => res.id !== reservationToDelete)
            );

            setDeletionMessage('Réservation supprimée avec succès.');
            setIsDeleteModalOpen(false);
            setReservationToDelete(null);
        } catch (error) {
            console.error('Erreur lors de la suppression:', error);
            setDeletionMessage(error.message || 'Erreur lors de la suppression');
        }
        setTimeout(() => setDeletionMessage(''), 5000);
    };

    const handleCloseEditModal = () => {
        setIsEditModalOpen(false);
        setFormData({
            id: null,
            name: '',
            date: '',
            timeSlot: '',
            accepted: false,
            rejected: false,
            userId: ''
        });
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData({
            ...formData,
            [name]: value
        });
    };
    
    // Gérer le changement du filtre de paiement
    const handleFilterChange = (e) => {
        setPaymentFilter(e.target.value);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            console.log('Mise à jour de la réservation:', formData);
            const response = await reservationService.updateReservation(formData.id, formData);
            
            // Mettre à jour la liste des réservations
            setReservations(prevReservations => 
                prevReservations.map(res => 
                    res.id === response.data.id ? response.data : res
                )
            );

            setConfirmationMessage('Réservation modifiée avec succès.');
            setIsEditModalOpen(false);
        } catch (error) {
            console.error('Erreur lors de la modification:', error);
            setConfirmationMessage(error.message || 'Erreur lors de la modification');
        }
        setTimeout(() => setConfirmationMessage(''), 5000);
    };

    const handleAccept = (id) => {
        // Implement the logic to accept a reservation
    };

    const handleReject = (id) => {
        // Implement the logic to reject a reservation
    };

    const handlePaymentClick = (reservation) => {
        setPaymentForm({
            ...paymentForm,
            reservationId: reservation.id,
            amount: reservation.terrainPrice // Utilisation du prix du terrain
        });
        setIsPaymentModalOpen(true);
    };

    const handlePaymentChange = (e) => {
        const { name, value } = e.target;
        setPaymentForm({
            ...paymentForm,
            [name]: value
        });
    };

    const handlePaymentSubmit = async (e) => {
        e.preventDefault();
        try {
            console.log('Paiement pour la réservation:', paymentForm.reservationId);
            await reservationService.markAsPaid(paymentForm.reservationId, user.username);
            
            // Mettre à jour la liste des réservations
            setReservations(prevReservations => 
                prevReservations.map(res => 
                    res.id === paymentForm.reservationId 
                        ? { ...res, isPaid: true, is_paid: true } 
                        : res
                )
            );

            setConfirmationMessage('Paiement effectué avec succès.');
            setIsPaymentModalOpen(false);
            setPaymentForm({
                cardNumber: '',
                cardName: '',
                expiryDate: '',
                cvv: '',
                amount: '',
                reservationId: ''
            });
        } catch (error) {
            console.error('Erreur lors du paiement:', error);
            setConfirmationMessage(error.message || 'Erreur lors du paiement');
        }
        setTimeout(() => setConfirmationMessage(''), 5000);
    };

    const today = new Date().toISOString().split('T')[0];
    const timeSlots = [
        "09:00-10:00", "10:00-11:00", "11:00-12:00",
        "15:00-16:00", "16:00-17:00", "17:00-18:00",
        "18:00-19:00", "19:00-20:00", "20:00-21:00",
        "21:00-22:00"
    ];

    return (
        <div className="reservation-container">
            <h1>
                {user.role === 'admin'
                    ? 'Toutes les Réservations'
                    : 'Historique des Réservations'}
            </h1>
            {confirmationMessage && (
                <div className="confirmation-message">
                    {confirmationMessage}
                </div>
            )}
            {deletionMessage && (
                <div className="deletion-message">
                    {deletionMessage}
                </div>
            )}
            
            {/* Filtre pour les administrateurs */}
            {user.role === 'admin' && (
                <div className="admin-filter">
                    <label htmlFor="payment-filter">Filtrer par statut de paiement:</label>
                    <select 
                        id="payment-filter" 
                        value={paymentFilter} 
                        onChange={handleFilterChange}
                        className="payment-filter-select"
                    >
                        <option value="all">Toutes les réservations</option>
                        <option value="paid">Réservations payées</option>
                        <option value="unpaid">Réservations non payées</option>
                    </select>
                </div>
            )}

            <table className="reservation-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Date</th>
                        <th>Plage Horaire</th>
                        {user.role === 'admin' && <th>Utilisateur</th>}
                        {user.role === 'admin' && <th>Statut de Paiement</th>}
                        {user.role === 'admin' && <th>Jours Restants</th>}
                        <th>Actions</th>
                        {user.role !== 'admin' && <th>Paiement</th>}
                    </tr>
                </thead>
                <tbody>
                    {displayedReservations.length === 0 ? (
                        <tr>
                            <td colSpan={user.role === 'admin' ? 7 : 4} className="no-reservations">
                                Aucune réservation disponible.
                            </td>
                        </tr>
                    ) : (
                        displayedReservations.map(reservation => {
                            const daysRemaining = calculateDaysRemaining(reservation.date);
                            return (
                                <tr key={reservation.id} className={
                                    paymentStatus[reservation.id] === 'success' || reservation.isPaid
                                        ? 'accepted' 
                                        : 'pending'
                                }>
                                    <td>{reservation.name}</td>
                                    <td>{reservation.date}</td>
                                    <td>{reservation.timeSlot}</td>
                                    {user.role === 'admin' && <td>{reservation.userId || 'Non assigné'}</td>}
                                    {user.role === 'admin' && (
                                        <td className="status-cell">
                                            {paymentStatus[reservation.id] === 'success' || reservation.isPaid ? (
                                                <div className="payment-success">
                                                    <i className="fas fa-check-circle"></i> Payée
                                                </div>
                                            ) : (
                                                <div className="status-rejected">
                                                    <i className="fas fa-times-circle"></i> Non payée
                                                </div>
                                            )}
                                        </td>
                                    )}
                                    {user.role === 'admin' && (
                                        <td className="days-remaining-cell">
                                            {daysRemaining < 0 ? (
                                                <span className="days-past">
                                                    <i className="fas fa-calendar-times"></i> Passé ({Math.abs(daysRemaining)} j)
                                                </span>
                                            ) : daysRemaining === 0 ? (
                                                <span className="days-today">
                                                    <i className="fas fa-calendar-day"></i> Aujourd'hui
                                                </span>
                                            ) : daysRemaining <= 3 ? (
                                                <span className="days-soon">
                                                    <i className="fas fa-calendar-alt"></i> {daysRemaining} jour{daysRemaining > 1 ? 's' : ''}
                                                </span>
                                            ) : (
                                                <span className="days-normal">
                                                    <i className="fas fa-calendar-alt"></i> {daysRemaining} jours
                                                </span>
                                            )}
                                        </td>
                                    )}
                                    <td className="actions">
                                        {user.role !== 'admin' && (
                                            <button onClick={() => handleModifyClick(reservation)} className="btn-modify">
                                                <i className="fas fa-edit"></i> Modifier
                                            </button>
                                        )}
                                        <button onClick={() => handleDeleteClick(reservation.id)} className="btn">
                                            <i className="fas fa-trash-alt"></i> Supprimer
                                        </button>
                                    </td>
                                    {user.role !== 'admin' && (
                                        <td className="payment-cell">
                                            {!paymentStatus[reservation.id] && !reservation.isPaid && (
                                                <button
                                                    onClick={() => handlePaymentClick(reservation)}
                                                    className="btn-payment"
                                                >
                                                    <i className="fas fa-credit-card"></i> Payer
                                                </button>
                                            )}
                                            {(paymentStatus[reservation.id] === 'success' || reservation.isPaid) && (
                                                <span className="payment-success">
                                                    <i className="fas fa-check-circle"></i> Paiement Réussi
                                                </span>
                                            )}
                                        </td>
                                    )}
                                </tr>
                            );
                        })
                    )}
                </tbody>
            </table>

            {isEditModalOpen && (
                <div className="modal">
                    <div className="modal-content">
                        <h2>Modifier Réservation</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="form-group">
                                <label><i className="fas fa-user"></i> Nom:</label>
                                <input type="text" name="name" value={formData.name} onChange={handleChange} required />
                            </div>
                            <div className="form-group">
                                <label><i className="fas fa-calendar-alt"></i> Date de Réservation:</label>
                                <input type="date" name="date" min={today} value={formData.date} onChange={handleChange} required />
                            </div>
                            <div className="form-group">
                                <label><i className="fas fa-clock"></i> Plage Horaire:</label>
                                <select name="timeSlot" value={formData.timeSlot || ''} onChange={handleChange} required>
                                    <option value="" disabled>Choisir une heure</option>
                                    {timeSlots.map(slot => {
                                        const isReserved = reservations.some(
                                            reservation =>
                                                reservation.date === formData.date &&
                                                reservation.timeSlot === slot &&
                                                reservation.id !== formData.id
                                        );
                                        return (
                                            <option
                                                key={slot}
                                                value={slot}
                                                disabled={isReserved}
                                            >
                                                {isReserved ? `${slot} (Déjà réservé)` : slot}
                                            </option>
                                        );
                                    })}
                                </select>
                            </div>
                            {user.role === 'admin' && (
                                <div className="form-group">
                                    <label>Utilisateur:</label>
                                    <input
                                        type="text"
                                        name="userId"
                                        value={formData.userId || ''}
                                        onChange={handleChange}
                                        readOnly={formData.userId !== user.username}
                                    />
                                </div>
                            )}

                            <div className="modal-actions">
                                <button type="submit" className="btn-modify">
                                    <i className="fas fa-check"></i> Enregistrer
                                </button>
                                <button type="button" className="btn" onClick={handleCloseEditModal}>
                                    <i className="fas fa-times"></i> Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {isDeleteModalOpen && (
                <div className="modal">
                    <div className="modal-content">
                        <h2>Êtes-vous sûr de vouloir supprimer cette réservation ?</h2>
                        <div className="modal-actions">
                            <button className="btn-modify" onClick={confirmDelete}>
                                <i className="fas fa-check"></i> Confirmer
                            </button>
                            <button className="btn" onClick={() => setIsDeleteModalOpen(false)}>
                                <i className="fas fa-times"></i> Annuler
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {isPaymentModalOpen && (
                <div className="modal">
                    <div className="modal-content">
                        <h2>Paiement</h2>
                        <form onSubmit={handlePaymentSubmit} className="tournoi-form">
                            <div className="form-group">
                                <label>Numéro de carte:</label>
                                <input
                                    type="text"
                                    name="cardNumber"
                                    value={paymentForm.cardNumber}
                                    onChange={handlePaymentChange}
                                    required
                                    placeholder="1234 5678 9012 3456"
                                    maxLength="19"
                                />
                            </div>
                            <div className="form-group">
                                <label>Nom sur la carte:</label>
                                <input
                                    type="text"
                                    name="cardName"
                                    value={paymentForm.cardName}
                                    onChange={handlePaymentChange}
                                    required
                                    placeholder="JEAN DUPONT"
                                />
                            </div>
                            <div className="form-group">
                                <label>Date d'expiration:</label>
                                <input
                                    type="text"
                                    name="expiryDate"
                                    value={paymentForm.expiryDate}
                                    onChange={handlePaymentChange}
                                    required
                                    placeholder="MM/AA"
                                    maxLength="5"
                                />
                            </div>
                            <div className="form-group">
                                <label>CVV:</label>
                                <input
                                    type="text"
                                    name="cvv"
                                    value={paymentForm.cvv}
                                    onChange={handlePaymentChange}
                                    required
                                    placeholder="123"
                                    maxLength="3"
                                />
                            </div>
                            <div className="form-group">
                                <label>Montant à payer:</label>
                                <input
                                    type="text"
                                    name="amount"
                                    value={`${paymentForm.amount} DH`}
                                    readOnly
                                    className="readonly-input"
                                />
                            </div>
                            <div className="modal-actions">
                                <button
                                    type="submit"
                                    className={`btn-modify ${paymentStatus[paymentForm.reservationId] === 'success' ? 'success' : ''}`}
                                    disabled={paymentStatus[paymentForm.reservationId] === 'success'}
                                >
                                    <i className="fas fa-credit-card"></i> Payer
                                </button>
                                <button
                                    type="button"
                                    className="btn"
                                    onClick={() => {
                                        setIsPaymentModalOpen(false);
                                        setPaymentForm({
                                            cardNumber: '',
                                            cardName: '',
                                            expiryDate: '',
                                            cvv: '',
                                            amount: '',
                                            reservationId: ''
                                        });
                                    }}
                                >
                                    <i className="fas fa-times"></i> Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

export default Reservation;