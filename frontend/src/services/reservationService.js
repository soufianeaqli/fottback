import * as directReservationService from './directReservationService';

// Récupérer toutes les réservations
export const getAllReservations = async () => {
    return directReservationService.getAllReservations();
};

// Récupérer les réservations d'un utilisateur par son username
export const getUserReservations = async (username) => {
    return directReservationService.getUserReservations(username);
};

// Créer une nouvelle réservation
export const createReservation = async (reservationData) => {
    return directReservationService.createReservation(reservationData);
};

// Mettre à jour une réservation
export const updateReservation = async (id, reservationData) => {
    return directReservationService.updateReservation(id, reservationData);
};

// Supprimer une réservation
export const deleteReservation = async (id, userId = null, isAdmin = false) => {
    return directReservationService.deleteReservation(id, userId, isAdmin);
};

// Vérifier la disponibilité d'un terrain
export const checkAvailability = async (terrainId, date, timeSlot) => {
    return directReservationService.checkAvailability(terrainId, date, timeSlot);
};

// Accepter une réservation (pour les admins)
export const acceptReservation = async (id) => {
    // Pour le moment, cette fonction n'est pas implémentée en direct
    // On pourrait implémenter un script direct supplémentaire si nécessaire
    console.warn('acceptReservation: Cette fonction utilise le service direct de mise à jour');
    return directReservationService.updateReservation(id, { accepted: true });
};

// Rejeter une réservation (pour les admins)
export const rejectReservation = async (id) => {
    // Pour le moment, cette fonction n'est pas implémentée en direct
    // On pourrait implémenter un script direct supplémentaire si nécessaire
    console.warn('rejectReservation: Cette fonction utilise le service direct de mise à jour');
    return directReservationService.updateReservation(id, { rejected: true });
};

// Marquer une réservation comme payée
export const markAsPaid = async (id, userId = null) => {
    return directReservationService.markAsPaid(id, userId);
}; 