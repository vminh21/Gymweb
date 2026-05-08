import { useParams, useNavigate } from 'react-router-dom';
import '../Member/Member.css';

function ExercisePage() {
  const { type } = useParams();
  const navigate = useNavigate();

  const data = {
    bodybuilding: { title: 'Bài tập Thể hình (Bodybuilding)', desc: 'Các bài tập tập trung phát triển kích thước và sức mạnh cơ bắp.' },
    cardio:       { title: 'Bài tập Cardio', desc: 'Tăng cường sức khỏe tim mạch và đốt cháy lượng mỡ thừa hiệu quả.' },
    crossfit:     { title: 'Hệ thống CrossFit', desc: 'Kết hợp nhiều bài tập cường độ cao giúp phát triển thể lực toàn diện.' },
    fitness:      { title: 'Fitness cơ bản', desc: 'Các bài tập duy trì vóc dáng, phù hợp mọi đối tượng luyện tập.' }
  };

  const info = data[type] || data.fitness;

  return (
    <div className="profile-page">
      <nav className="member-navbar">
        <div className="member-nav-brand" onClick={() => navigate(-1)} style={{cursor:'pointer'}}><span className="brand-name">FitPhysique</span></div>
        <div className="member-nav-links"><button onClick={() => navigate(-1)} style={{background:'none',border:'none',color:'#f97316',cursor:'pointer',fontSize:'1rem'}}><i className="bx bx-arrow-back"></i> Quay lại</button></div>
      </nav>
      <div className="profile-content" style={{textAlign:'center',paddingTop:100}}>
        <i className="bx bx-dumbbell" style={{fontSize:'5rem',color:'#f97316',marginBottom:20}}></i>
        <h1 style={{color:'#fff',fontSize:'2.5rem',marginBottom:16}}>{info.title}</h1>
        <p style={{color:'#888',fontSize:'1.1rem',maxWidth:600,margin:'0 auto 40px'}}>{info.desc}</p>
        
        <div style={{padding:40,background:'rgba(255,255,255,0.05)',borderRadius:20,border:'1px solid rgba(255,255,255,0.1)',color:'#888'}}>
          (Tính năng thư viện video / hình ảnh mẫu đang được cập nhật...)
        </div>
      </div>
    </div>
  );
}

export default ExercisePage;
