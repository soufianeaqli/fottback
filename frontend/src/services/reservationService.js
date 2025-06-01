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
    try {
        console.log('Données de mise à jour:', reservationData);
        
        // Préparer les données à envoyer
        const updatedData = {
            name: reservationData.name,
            date: reservationData.date,
            time_slot: reservationData.timeSlot,
            // Conserver le statut de paiement existant
            is_paid: reservationData.isPaid
        };

        // Utiliser le script PHP direct sans CSRF
        const response = await fetch(`http://127.0.0.1:8000/direct-update-reservation.php?id=${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(updatedData)
        });
        
        if (!response.ok) {
            throw new Error('Erreur lors de la mise à jour de la réservation');
        }
        
        const data = await response.json();
        
        // Déterminer le nom du terrain à partir de toutes les sources possibles
        const terrainName = 
            reservationData.terrainName || 
            reservationData.terrain_name || 
            (reservationData.terrain && reservationData.terrain.titre) || 
            (reservationData.terrain && reservationData.terrain.name) ||
            data.terrain_name ||
            (data.terrain && data.terrain.titre) ||
            (data.terrain && data.terrain.name) ||
            null;
        
        // Déterminer l'ID du terrain à partir de toutes les sources possibles
        const terrainId = 
            reservationData.terrainId || 
            reservationData.terrain_id ||
            (reservationData.terrain && reservationData.terrain.id) ||
            data.terrain_id ||
            (data.terrain && data.terrain.id) ||
            null;
        
        console.log('Nom de terrain déterminé:', terrainName);
        console.log('ID de terrain déterminé:', terrainId);
        
        // Retourner les données mises à jour
        return {
            data: {
                id: id,
                name: reservationData.name,
                date: reservationData.date,
                timeSlot: reservationData.timeSlot,
                userId: reservationData.userId,
                // Conserver toutes les informations terrain possibles
                terrain_name: terrainName,
                terrainName: terrainName,
                terrain_id: terrainId,
                terrainId: terrainId,
                terrain: reservationData.terrain || data.terrain,
                // Préserver le statut de paiement
                isPaid: reservationData.isPaid || data.is_paid,
                accepted: reservationData.accepted,
                rejected: reservationData.rejected
            }
        };
    } catch (error) {
        console.error('Erreur lors de la mise à jour de la réservation:', error);
        throw error;
    }
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