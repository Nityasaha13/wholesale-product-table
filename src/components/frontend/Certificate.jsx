import React from 'react';

const Certificate = () => {
  const getCertificateData = () => {
    return {
      studentName: 'Jane Elizabeth Carter',
      courseName: 'Certified React Developer',
      issuedDate: 'March 15, 2025',
      level: 'Intermediate',
      certificateId: 'VERIFY-10MS9HBDDQ',
      instructorName: 'Thomas Thorsell-Arntsen',
      verificationUrl: 'https://verify.example.com/10MS9HBDDQ'
    };
  };

  const data = getCertificateData();

  return (
    <div className="certificate">
      <div className="certificate-border">
        <div className="certificate-inner">
          <div className="certificate-header">
            <img src="https://www.w3schools.com/images/w3schools_logo_436_2.png" alt="Logo" className="logo" />
            <h1>CERTIFICATE OF COMPLETION</h1>
          </div>

          <p className="certify-line">This certifies that</p>
          <h2 className="student-name">{data.studentName}</h2>
          <p className="course-line">
            has passed the <strong>{data.courseName}</strong> certification exam and is hereby declared a
          </p>

          <h3 className="course-title">{data.courseName}</h3>

          <div className="badge-section">
            <div className="badge">
              <span>{data.level}</span>
            </div>
          </div>

          <p className="exam-level">The candidate has passed the exam at the {data.level} level.</p>
          <p className="issue-date">Issued on {data.issuedDate}</p>

          <div className="signature-section">
            <img
              src="https://www.w3schools.com/images/thomas_signature.png"
              alt="Signature"
              className="signature-img"
            />
            <p className="instructor-name">{data.instructorName}</p>
            <p className="instructor-title">for W3Schools.com</p>
          </div>

          <div className="verification">
            <p>Verify completion at</p>
            <a href={data.verificationUrl} target="_blank" rel="noreferrer">
              {data.verificationUrl}
            </a>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Certificate;