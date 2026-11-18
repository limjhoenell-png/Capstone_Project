using System;
using System.Linq;

namespace FingerprintAttendance.Core
{
    internal static class PurposeOfVisitCatalog
    {
        internal const string BarangayClearance = "Request for Barangay Clearance";
        internal const string CertificateOfIndigency = "Request for Certificate of Indigency";
        internal const string CertificateOfResidency = "Request for Certificate of Residency";
        internal const string Complaint = "Filing a Complaint";
        internal const string BlotterReport = "Blotter Report";
        internal const string MediationSettlement = "Mediation / Settlement";
        internal const string Assistance = "Request for Assistance";
        internal const string BusinessPermitClearance = "Business Permit Clearance";
        internal const string CommunityTaxCertificate = "Community Tax Certificate (Cedula) Issuance";
        internal const string BarangayIdApplication = "Barangay ID Application";
        internal const string FirstTimeJobSeekerCertification = "First-Time Job Seeker Certification (RA 11261)";
        internal const string CertificateOfLowIncome = "Certificate of Low Income";
        internal const string BuildingPermitClearance = "Building / Construction Permit Clearance";
        internal const string SpecialSectorEndorsement = "Senior Citizen, PWD, or Solo Parent Endorsement & Registration";
        internal const string CertificateToFileAction = "Certificate to File Action (CFA) Issuance";
        internal const string Other = "Other";

        private static readonly string[] StandardOptions =
        {
            BarangayClearance,
            CertificateOfIndigency,
            CertificateOfResidency,
            Complaint,
            BlotterReport,
            MediationSettlement,
            Assistance,
            BusinessPermitClearance,
            CommunityTaxCertificate,
            BarangayIdApplication,
            FirstTimeJobSeekerCertification,
            CertificateOfLowIncome,
            BuildingPermitClearance,
            SpecialSectorEndorsement,
            CertificateToFileAction,
            Other
        };

        internal static string[] GetOptions()
        {
            return (string[])StandardOptions.Clone();
        }

        internal static string FormatOtherPurpose(string details)
        {
            string value = (details ?? string.Empty).Trim();
            return value.Length == 0 ? string.Empty : Other + ": " + value;
        }

        internal static bool IsValidStoredPurpose(string purpose)
        {
            string value = (purpose ?? string.Empty).Trim();
            if (value.Length == 0)
                return false;

            if (StandardOptions.Take(StandardOptions.Length - 1)
                .Contains(value, StringComparer.Ordinal))
            {
                return true;
            }

            string otherPrefix = Other + ": ";
            return value.StartsWith(otherPrefix, StringComparison.Ordinal) &&
                   value.Substring(otherPrefix.Length).Trim().Length > 0;
        }
    }
}
