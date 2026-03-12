import { PrismaClient } from '@prisma/client';
import { hash } from 'bcryptjs';

const prisma = new PrismaClient();

async function main() {
  console.log('🌱 Seeding database...');

  // Clean existing data
  await prisma.notification.deleteMany();
  await prisma.timelineStep.deleteMany();
  await prisma.invoiceItem.deleteMany();
  await prisma.invoice.deleteMany();
  await prisma.activity.deleteMany();
  await prisma.itineraryDay.deleteMany();
  await prisma.flight.deleteMany();
  await prisma.document.deleteMany();
  await prisma.mapPoint.deleteMany();
  await prisma.itinerary.deleteMany();
  await prisma.client.deleteMany();
  await prisma.user.deleteMany();
  await prisma.fAQ.deleteMany();
  await prisma.travelGuide.deleteMany();

  // Admin user
  const adminPassword = await hash('admin123', 12);
  await prisma.user.create({
    data: {
      email: 'admin@travelagency.com',
      password: adminPassword,
      role: 'ADMIN',
    },
  });
  console.log('✅ Created admin user: admin@travelagency.com / admin123');

  // Client 1: Sarah Johnson — European Adventure
  const clientPassword = await hash('demo123', 12);
  const client1 = await prisma.client.create({
    data: {
      firstName: 'Sarah',
      lastName: 'Johnson',
      phone: '+1 (555) 234-5678',
      nationality: 'American',
      passportNumber: 'US1234567',
      passportExpiry: new Date('2028-06-15'),
      user: {
        create: {
          email: 'client@example.com',
          password: clientPassword,
          role: 'CLIENT',
        },
      },
    },
  });
  console.log('✅ Created client: client@example.com / demo123');

  // Itinerary for Sarah
  const startDate = new Date();
  startDate.setDate(startDate.getDate() + 30);
  const endDate = new Date(startDate);
  endDate.setDate(endDate.getDate() + 10);

  const itinerary1 = await prisma.itinerary.create({
    data: {
      clientId: client1.id,
      title: 'European Dream Vacation',
      description: 'A magical 10-day journey through Paris, Rome, and Barcelona.',
      destination: 'Paris, Rome & Barcelona',
      startDate,
      endDate,
      days: {
        create: [
          {
            dayNumber: 1,
            date: startDate,
            title: 'Arrival in Paris',
            description: 'Welcome to the City of Light! Check in and explore the neighborhood.',
            location: 'Paris, France',
            activities: {
              create: [
                { name: 'Arrive at CDG Airport', time: '14:00', type: 'TRANSPORT', location: 'Charles de Gaulle Airport' },
                { name: 'Check-in at Hotel Le Marais', time: '16:00', type: 'ACCOMMODATION', location: '15 Rue de Bretagne, Paris', lat: 48.8631, lng: 2.3602 },
                { name: 'Welcome dinner at Le Comptoir', time: '19:30', type: 'DINING', location: '9 Carrefour de l\'Odéon, Paris', lat: 48.8517, lng: 2.3384 },
              ],
            },
          },
          {
            dayNumber: 2,
            date: new Date(startDate.getTime() + 86400000),
            title: 'Paris Highlights',
            description: 'Visit the iconic Eiffel Tower and Louvre Museum.',
            location: 'Paris, France',
            activities: {
              create: [
                { name: 'Breakfast at Café de Flore', time: '08:30', type: 'DINING', location: '172 Bd Saint-Germain, Paris', lat: 48.8541, lng: 2.3326 },
                { name: 'Louvre Museum', time: '10:00', type: 'SIGHTSEEING', location: 'Rue de Rivoli, Paris', lat: 48.8606, lng: 2.3376, description: 'World\'s largest art museum. Book tickets in advance!' },
                { name: 'Eiffel Tower', time: '15:00', type: 'SIGHTSEEING', location: 'Champ de Mars, Paris', lat: 48.8584, lng: 2.2945 },
                { name: 'Seine River Cruise', time: '19:00', type: 'EXCURSION', location: 'Port de la Bourdonnais, Paris', lat: 48.8594, lng: 2.3040 },
              ],
            },
          },
          {
            dayNumber: 5,
            date: new Date(startDate.getTime() + 4 * 86400000),
            title: 'Fly to Rome',
            description: 'Travel to the Eternal City and discover ancient history.',
            location: 'Rome, Italy',
            activities: {
              create: [
                { name: 'Flight CDG → FCO (AF1234)', time: '09:00', type: 'TRANSPORT', location: 'Charles de Gaulle Airport' },
                { name: 'Check-in at Hotel Minerva', time: '14:00', type: 'ACCOMMODATION', location: 'Piazza della Minerva, Rome', lat: 41.8973, lng: 12.4780 },
                { name: 'Trevi Fountain & Pantheon', time: '16:00', type: 'SIGHTSEEING', location: 'Piazza di Trevi, Rome', lat: 41.9009, lng: 12.4833 },
              ],
            },
          },
        ],
      },
      flights: {
        create: [
          {
            flightNumber: 'AF1234',
            airline: 'Air France',
            origin: 'New York',
            originCode: 'JFK',
            destination: 'Paris',
            destinationCode: 'CDG',
            departureTime: new Date(startDate.getTime() - 8 * 3600 * 1000),
            arrivalTime: startDate,
            terminal: 'T2E',
            gate: 'G22',
            icao24: 'a4e8c2',
          },
          {
            flightNumber: 'AZ0201',
            airline: 'ITA Airways',
            origin: 'Paris',
            originCode: 'CDG',
            destination: 'Rome',
            destinationCode: 'FCO',
            departureTime: new Date(startDate.getTime() + 4 * 86400000 + 9 * 3600 * 1000),
            arrivalTime: new Date(startDate.getTime() + 4 * 86400000 + 11 * 3600 * 1000),
            terminal: 'T2C',
            gate: 'C14',
          },
          {
            flightNumber: 'VY8803',
            airline: 'Vueling',
            origin: 'Rome',
            originCode: 'FCO',
            destination: 'Barcelona',
            destinationCode: 'BCN',
            departureTime: new Date(startDate.getTime() + 7 * 86400000 + 7 * 3600 * 1000),
            arrivalTime: new Date(startDate.getTime() + 7 * 86400000 + 9 * 3600 * 1000),
            terminal: 'T1',
            gate: 'B8',
          },
        ],
      },
      documents: {
        create: [
          { name: 'US Passport', type: 'PASSPORT', required: true, status: 'APPROVED', notes: 'Valid until June 2028' },
          { name: 'France/EU Entry Visa', type: 'VISA', required: false, status: 'APPROVED', notes: 'US citizens do not require a visa for stays up to 90 days' },
          { name: 'Travel Insurance Certificate', type: 'INSURANCE', required: true, status: 'SUBMITTED', dueDate: new Date(startDate.getTime() - 7 * 86400000) },
          { name: 'COVID-19 Vaccination Proof', type: 'VACCINATION', required: false, status: 'APPROVED' },
          { name: 'Hotel Booking Confirmations', type: 'HOTEL_BOOKING', required: true, status: 'PENDING', notes: 'Please submit all hotel booking confirmation emails', dueDate: new Date(startDate.getTime() - 14 * 86400000) },
        ],
      },
      mapPoints: {
        create: [
          { name: 'Charles de Gaulle Airport', lat: 49.0097, lng: 2.5479, type: 'airport', dayNumber: 1 },
          { name: 'Hotel Le Marais', lat: 48.8631, lng: 2.3602, type: 'hotel', dayNumber: 1 },
          { name: 'Eiffel Tower', lat: 48.8584, lng: 2.2945, type: 'activity', dayNumber: 2 },
          { name: 'Louvre Museum', lat: 48.8606, lng: 2.3376, type: 'activity', dayNumber: 2 },
          { name: 'Café de Flore', lat: 48.8541, lng: 2.3326, type: 'restaurant', dayNumber: 2 },
          { name: 'Rome Fiumicino Airport', lat: 41.8003, lng: 12.2388, type: 'airport', dayNumber: 5 },
          { name: 'Hotel Minerva', lat: 41.8973, lng: 12.4780, type: 'hotel', dayNumber: 5 },
          { name: 'Trevi Fountain', lat: 41.9009, lng: 12.4833, type: 'activity', dayNumber: 5 },
        ],
      },
    },
  });

  // Timeline steps
  await prisma.timelineStep.createMany({
    data: [
      { clientId: client1.id, title: 'Submit travel insurance certificate', description: 'Upload your insurance policy documents to the portal', dueDate: new Date(startDate.getTime() - 21 * 86400000), order: 1, completed: true, completedAt: new Date() },
      { clientId: client1.id, title: 'Provide hotel booking confirmations', description: 'Send confirmation emails for all hotels', dueDate: new Date(startDate.getTime() - 14 * 86400000), order: 2 },
      { clientId: client1.id, title: 'Pay remaining trip balance', description: 'Final payment due before departure', dueDate: new Date(startDate.getTime() - 7 * 86400000), order: 3 },
      { clientId: client1.id, title: 'Download offline maps', description: 'Download Google Maps offline for Paris, Rome, and Barcelona', dueDate: new Date(startDate.getTime() - 3 * 86400000), order: 4 },
      { clientId: client1.id, title: 'Check-in for outbound flight online', description: 'Online check-in opens 24 hours before departure', dueDate: new Date(startDate.getTime() - 1 * 86400000), order: 5 },
    ],
  });

  // Invoices
  const inv1 = await prisma.invoice.create({
    data: {
      clientId: client1.id,
      invoiceNumber: 'INV-2024-001',
      amount: 1500,
      currency: 'USD',
      status: 'PAID',
      dueDate: new Date(startDate.getTime() - 60 * 86400000),
      paidAt: new Date(startDate.getTime() - 58 * 86400000),
      description: 'Trip deposit (25%)',
      items: {
        create: [
          { description: 'Trip deposit', amount: 1500, quantity: 1 },
        ],
      },
    },
  });

  await prisma.invoice.create({
    data: {
      clientId: client1.id,
      invoiceNumber: 'INV-2024-002',
      amount: 3200,
      currency: 'USD',
      status: 'PENDING',
      dueDate: new Date(startDate.getTime() - 7 * 86400000),
      description: 'Remaining balance - European Dream Vacation',
      items: {
        create: [
          { description: 'Flights (return JFK-CDG + internal EU)', amount: 1200, quantity: 1 },
          { description: 'Hotel accommodations (10 nights)', amount: 1600, quantity: 1 },
          { description: 'Tour packages & excursions', amount: 400, quantity: 1 },
        ],
      },
    },
  });

  // Notifications
  await prisma.notification.createMany({
    data: [
      {
        clientId: client1.id,
        title: 'Payment Due Soon',
        message: 'Invoice INV-2024-002 for USD 3,200.00 is due in 7 days.',
        type: 'PAYMENT_DUE',
        read: false,
      },
      {
        clientId: client1.id,
        title: 'Document Required',
        message: '"Hotel Booking Confirmations" is due before departure.',
        type: 'DOCUMENT_REQUIRED',
        read: false,
      },
      {
        clientId: client1.id,
        title: 'Deposit Payment Confirmed',
        message: 'Your deposit of USD 1,500.00 has been received. Thank you!',
        type: 'GENERAL',
        read: true,
      },
    ],
  });

  // Client 2: James Chen — Thailand Escape
  const client2 = await prisma.client.create({
    data: {
      firstName: 'James',
      lastName: 'Chen',
      phone: '+1 (555) 876-5432',
      nationality: 'American',
      user: {
        create: {
          email: 'james@example.com',
          password: clientPassword,
          role: 'CLIENT',
        },
      },
    },
  });

  const startDate2 = new Date();
  startDate2.setDate(startDate2.getDate() + 60);
  const endDate2 = new Date(startDate2);
  endDate2.setDate(endDate2.getDate() + 7);

  await prisma.itinerary.create({
    data: {
      clientId: client2.id,
      title: 'Thailand Island Escape',
      description: '7 days exploring the stunning islands and beaches of southern Thailand.',
      destination: 'Bangkok & Koh Samui, Thailand',
      startDate: startDate2,
      endDate: endDate2,
      flights: {
        create: [
          {
            flightNumber: 'TG418',
            airline: 'Thai Airways',
            origin: 'Los Angeles',
            originCode: 'LAX',
            destination: 'Bangkok',
            destinationCode: 'BKK',
            departureTime: new Date(startDate2.getTime() - 15 * 3600 * 1000),
            arrivalTime: startDate2,
          },
        ],
      },
      documents: {
        create: [
          { name: 'US Passport', type: 'PASSPORT', required: true, status: 'APPROVED' },
          { name: 'Travel Insurance', type: 'INSURANCE', required: true, status: 'PENDING', dueDate: new Date(startDate2.getTime() - 14 * 86400000) },
        ],
      },
      mapPoints: {
        create: [
          { name: 'Bangkok Suvarnabhumi Airport', lat: 13.6900, lng: 100.7501, type: 'airport', dayNumber: 1 },
          { name: 'Koh Samui', lat: 9.5120, lng: 100.0136, type: 'activity', dayNumber: 3 },
        ],
      },
    },
  });

  await prisma.invoice.create({
    data: {
      clientId: client2.id,
      invoiceNumber: 'INV-2024-003',
      amount: 4500,
      currency: 'USD',
      status: 'PENDING',
      dueDate: new Date(startDate2.getTime() - 30 * 86400000),
      description: 'Thailand Island Escape - Full Package',
    },
  });

  // Travel guides
  await prisma.travelGuide.createMany({
    data: [
      {
        title: 'Packing Essentials',
        icon: '🧳',
        content: 'Pack light with versatile clothing that can be layered. Essential items:\n\n• Valid passport (check expiry!)\n• Universal power adapter\n• Comfortable walking shoes\n• Portable phone charger/power bank\n• Travel-size toiletries\n• Any prescription medications\n• Copies of all important documents',
        order: 1,
      },
      {
        title: 'Money & Currency',
        icon: '💶',
        content: 'Notify your bank before traveling to avoid card blocks. Tips:\n\n• Carry a mix of cash and cards\n• Use ATMs at airports/banks (lower fees)\n• Avoid currency exchange at tourist spots\n• Keep small bills for tips and small purchases\n• Revolut or Wise travel cards offer great exchange rates',
        order: 2,
      },
      {
        title: 'Health & Safety',
        icon: '🏥',
        content: 'Stay safe and healthy during your travels:\n\n• Get any required vaccinations 4-6 weeks before departure\n• Carry a basic first aid kit\n• Know your travel insurance emergency contact\n• Save the local emergency number: 112 (EU), 191 (Thailand)\n• Stay hydrated and use sunscreen\n• Avoid drinking tap water in some destinations',
        order: 3,
      },
      {
        title: 'Transportation Tips',
        icon: '🚂',
        content: 'Getting around efficiently:\n\n• Download Citymapper or Google Maps for navigation\n• In Europe, trains are often faster and more scenic than flights\n• Use official taxis or Uber/Bolt to avoid overcharging\n• Consider getting a local SIM card or pocket WiFi\n• Validate your metro ticket before boarding in Europe',
        order: 4,
      },
    ],
  });

  // FAQs
  await prisma.fAQ.createMany({
    data: [
      {
        question: 'How do I access my itinerary?',
        answer: 'Log in to your travel portal with the email and password provided by your travel agent. Navigate to "My Itinerary" in the sidebar to view your day-by-day schedule, flights, and destination map.',
        category: 'Portal',
        order: 1,
      },
      {
        question: 'How do I pay my invoice?',
        answer: 'Go to the "Invoices" section in your portal. Click on any pending invoice and press "Pay Now". You\'ll be directed to a secure Stripe payment page. We accept all major credit and debit cards.',
        category: 'Payments',
        order: 2,
      },
      {
        question: 'What if my flight is delayed or cancelled?',
        answer: 'Check your flight status in real-time on the Flights tab of your itinerary. If there\'s a significant disruption, contact your travel agent immediately. We\'ll work to rebook you as quickly as possible.',
        category: 'Flights',
        order: 3,
      },
      {
        question: 'What documents do I need for international travel?',
        answer: 'At minimum, you\'ll need a valid passport (with at least 6 months validity beyond your travel dates). Depending on your destination and nationality, you may also need visas, vaccination certificates, or travel insurance. Check the Documents section of your portal for your specific requirements.',
        category: 'Documents',
        order: 4,
      },
      {
        question: 'How early should I arrive at the airport?',
        answer: 'For international flights, we recommend arriving 3 hours before departure. For domestic flights, 2 hours is usually sufficient. During peak travel seasons or at busy airports, allow extra time.',
        category: 'Flights',
        order: 5,
      },
      {
        question: 'Can I modify my itinerary?',
        answer: 'Contact your travel agent to request any modifications to your itinerary. Changes may be subject to availability and additional fees depending on the suppliers involved.',
        category: 'General',
        order: 6,
      },
      {
        question: 'What is the cancellation policy?',
        answer: 'Cancellation policies vary by booking and supplier. Generally: cancelling 30+ days before departure may result in loss of deposit; 14-29 days: 50% refund; less than 14 days: no refund. Please review your booking terms or contact your agent for specific details.',
        category: 'General',
        order: 7,
      },
      {
        question: 'How do I contact my travel agent?',
        answer: 'You can reach your travel agent via email at the address on your booking confirmation, or by phone during business hours. For emergencies during your trip, use the emergency contact number provided in your travel documents.',
        category: 'Portal',
        order: 8,
      },
    ],
  });

  console.log('✅ Travel guides and FAQs created');
  console.log('✅ Created client 2: james@example.com / demo123');
  console.log('\n🎉 Database seeded successfully!');
  console.log('\n📋 Login credentials:');
  console.log('  Admin:    admin@travelagency.com / admin123');
  console.log('  Client 1: client@example.com    / demo123');
  console.log('  Client 2: james@example.com     / demo123');
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
