import nodemailer from 'nodemailer';

const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST ?? 'smtp.gmail.com',
  port: Number(process.env.SMTP_PORT ?? 587),
  secure: false,
  auth:
    process.env.SMTP_USER && process.env.SMTP_PASS
      ? {
          user: process.env.SMTP_USER,
          pass: process.env.SMTP_PASS,
        }
      : undefined,
});

interface EmailOptions {
  to: string;
  subject: string;
  html: string;
}

export async function sendEmail(options: EmailOptions): Promise<boolean> {
  if (!process.env.SMTP_USER || !process.env.SMTP_PASS) {
    console.log('[Email] SMTP not configured, skipping:', options.subject);
    return false;
  }

  try {
    await transporter.sendMail({
      from: `"Travel Portal" <${process.env.SMTP_USER}>`,
      ...options,
    });
    return true;
  } catch (error) {
    console.error('[Email] Failed to send:', error);
    return false;
  }
}

export function renderPaymentDueEmail(
  clientName: string,
  invoiceNumber: string,
  amount: number,
  currency: string,
  dueDate: Date,
  portalUrl: string,
): string {
  return `
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
      <div style="background: linear-gradient(135deg, #0369a1, #0ea5e9); padding: 32px; border-radius: 12px 12px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">Payment Reminder</h1>
      </div>
      <div style="padding: 32px; background: #f8fafc; border-radius: 0 0 12px 12px; border: 1px solid #e2e8f0;">
        <p style="font-size: 16px; color: #374151;">Dear ${clientName},</p>
        <p style="color: #6b7280;">This is a friendly reminder that the following invoice is due soon:</p>
        <div style="background: white; border-radius: 8px; padding: 20px; margin: 20px 0; border: 1px solid #e2e8f0;">
          <p><strong>Invoice:</strong> ${invoiceNumber}</p>
          <p><strong>Amount:</strong> ${currency} ${amount.toFixed(2)}</p>
          <p><strong>Due Date:</strong> ${dueDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
        </div>
        <a href="${portalUrl}/invoices" style="background: #0284c7; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block; margin-top: 16px;">
          View & Pay Invoice
        </a>
        <p style="color: #9ca3af; font-size: 14px; margin-top: 32px;">
          If you have any questions, please contact your travel agent.
        </p>
      </div>
    </div>
  `;
}

export function renderTripReminderEmail(
  clientName: string,
  tripTitle: string,
  startDate: Date,
  daysUntil: number,
  portalUrl: string,
): string {
  return `
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
      <div style="background: linear-gradient(135deg, #0369a1, #0ea5e9); padding: 32px; border-radius: 12px 12px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">✈️ Your Trip is Coming Up!</h1>
      </div>
      <div style="padding: 32px; background: #f8fafc; border-radius: 0 0 12px 12px; border: 1px solid #e2e8f0;">
        <p style="font-size: 16px; color: #374151;">Dear ${clientName},</p>
        <p style="color: #6b7280;">
          ${daysUntil === 1 ? 'Your trip starts <strong>tomorrow</strong>!' : `Your trip starts in <strong>${daysUntil} days</strong>!`}
        </p>
        <div style="background: white; border-radius: 8px; padding: 20px; margin: 20px 0; border: 1px solid #e2e8f0;">
          <p><strong>Trip:</strong> ${tripTitle}</p>
          <p><strong>Departure:</strong> ${startDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
        </div>
        <a href="${portalUrl}/itinerary" style="background: #0284c7; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block; margin-top: 16px;">
          View Your Itinerary
        </a>
        <p style="color: #9ca3af; font-size: 14px; margin-top: 32px;">
          Safe travels! Contact your travel agent if you need assistance.
        </p>
      </div>
    </div>
  `;
}

export function renderDocumentReminderEmail(
  clientName: string,
  documentName: string,
  dueDate: Date,
  portalUrl: string,
): string {
  return `
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
      <div style="background: linear-gradient(135deg, #0369a1, #0ea5e9); padding: 32px; border-radius: 12px 12px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">Document Required</h1>
      </div>
      <div style="padding: 32px; background: #f8fafc; border-radius: 0 0 12px 12px; border: 1px solid #e2e8f0;">
        <p style="font-size: 16px; color: #374151;">Dear ${clientName},</p>
        <p style="color: #6b7280;">A required document needs your attention:</p>
        <div style="background: white; border-radius: 8px; padding: 20px; margin: 20px 0; border: 1px solid #e2e8f0;">
          <p><strong>Document:</strong> ${documentName}</p>
          <p><strong>Due Date:</strong> ${dueDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
        </div>
        <a href="${portalUrl}/documents" style="background: #0284c7; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block; margin-top: 16px;">
          View Documents
        </a>
      </div>
    </div>
  `;
}
