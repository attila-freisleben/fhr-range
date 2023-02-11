# fhr-range
FHIR Range:  Generate realistic FHIR data

Generate realistic outpatient data for healthcare IT development in FHIR (HL7) format.
Patient age/gender distribution is based on census data, Encounter/Condition data mimics real-life healthcare data distribution.

Organization/HealthcareService/Location for a given area is generated according to population density.

Generated resources: 
- Organization
- HealthcareService
- Location
- Practitioner
- PractitionerRole
- Patient
- Encounter
- Condition
- Observation

Patient photos are generated using StyleGAN2.

Some metrics:
http://freisleben.hu:3000/d/iN64q6A4k/generated-fhir-data?orgId=2&var-country=All
