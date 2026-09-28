<?php

declare(strict_types=1);

final class MgDefensas
{
    public function activeWorks(): array
    {
        return Database::connection()->query(
            'SELECT w.id_trabajo,w.codigo,w.tema,w.estado AS estado_trabajo,m.nombre AS modalidad,
                    m.requiere_defensa,m.requiere_tribunal,c.codigo AS codigo_cohorte,c.nombre AS cohorte,
                    cl.resultado AS cierre
             FROM mg_trabajos w INNER JOIN mg_modalidades m ON m.id_modalidad=w.id_modalidad
             INNER JOIN mg_cohortes c ON c.id_cohorte=w.id_cohorte
             LEFT JOIN mg_cierres cl ON cl.id_trabajo=w.id_trabajo
             WHERE w.estado IN ("activo","finalizado")
             ORDER BY FIELD(w.estado,"activo","finalizado","cancelado"),c.codigo,w.codigo'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function overview(int $workId): ?array
    {
        $statement=Database::connection()->prepare(
            'SELECT w.*,m.nombre AS modalidad,m.requiere_tutor,m.requiere_informes,m.requiere_asistencia,
                    m.asistencia_minima_pct,m.requiere_mdg1,m.requiere_mdg2,m.requiere_informe_final,
                    m.requiere_tribunal,m.requiere_defensa,m.max_defensas,m.avance_requerido_defensa,
                    m.impide_tutor_tribunal,m.miembros_minimos_tribunal,
                    c.codigo AS codigo_cohorte,c.nombre AS cohorte,
                    cl.id_cierre,cl.resultado AS resultado_cierre,cl.observaciones AS nota_cierre,cl.cerrado_en
             FROM mg_trabajos w INNER JOIN mg_modalidades m ON m.id_modalidad=w.id_modalidad
             INNER JOIN mg_cohortes c ON c.id_cohorte=w.id_cohorte
             LEFT JOIN mg_cierres cl ON cl.id_trabajo=w.id_trabajo
             WHERE w.id_trabajo=:id LIMIT 1'
        );
        $statement->execute(['id'=>$workId]);$work=$statement->fetch(PDO::FETCH_ASSOC);
        if(!$work){return null;}
        $work['checklist']=$this->checklist($workId);
        $work['tribunales']=$this->tribunals($workId);
        $work['tribunal_actual']=$this->currentTribunalSummary($workId);
        $work['tribunal_miembros']=$work['tribunal_actual']?$this->tribunalMembers((int)$work['tribunal_actual']['id_tribunal']):[];
        $work['defensas']=$this->defenses($workId);
        return $work;
    }

    public function studentDefenseInfo(int $workId, int $studentId): array
    {
        $member = Database::connection()->prepare(
            'SELECT 1 FROM mg_trabajo_integrantes ti
             INNER JOIN mg_inscripciones i ON i.id_inscripcion=ti.id_inscripcion
             WHERE ti.id_trabajo=:work AND ti.id_estudiante=:student
               AND ti.estado IN ("activo","finalizado") AND i.estado IN ("activa","finalizada") LIMIT 1'
        );
        $member->execute(['work' => $workId, 'student' => $studentId]);
        if (!$member->fetchColumn()) {
            throw new RuntimeException('No tiene acceso al historial de defensa de este trabajo.');
        }
        return [
            'tribunal' => $this->tribunalMembersForStudent($workId),
            'defensas' => $this->studentDefenses($workId),
            'cierre' => $this->closure($workId),
        ];
    }

    private function tribunalMembersForStudent(int $workId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT t.designado_en,tm.rol,u.nombre,u.apellido,r.nombre_rol
             FROM mg_tribunales t INNER JOIN mg_tribunal_miembros tm ON tm.id_tribunal=t.id_tribunal
             INNER JOIN usuarios u ON u.id_usuario=tm.id_usuario INNER JOIN roles r ON r.id_rol=u.id_rol
             WHERE t.id_trabajo=:work AND t.estado="activo"
             ORDER BY FIELD(tm.rol,"presidente","miembro"),u.apellido,u.nombre'
        );
        $statement->execute(['work' => $workId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function studentDefenses(int $workId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT d.id_defensa,d.numero_defensa,d.fecha_hora,d.ubicacion,d.estado,d.resultado,d.nota,d.observaciones,
                    GROUP_CONCAT(DISTINCT CONCAT(tm.rol,": ",u.nombre," ",u.apellido) ORDER BY FIELD(tm.rol,"presidente","miembro"),u.apellido SEPARATOR "; ") AS tribunal
             FROM mg_defensas d
             LEFT JOIN mg_tribunal_miembros tm ON tm.id_tribunal=d.id_tribunal
             LEFT JOIN usuarios u ON u.id_usuario=tm.id_usuario
             WHERE d.id_trabajo=:work
             GROUP BY d.id_defensa,d.numero_defensa,d.fecha_hora,d.ubicacion,d.estado,d.resultado,d.nota,d.observaciones
             ORDER BY d.numero_defensa'
        );
        $statement->execute(['work' => $workId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function checklist(int $workId): array
    {
        $query=Database::connection()->prepare(
            'SELECT w.estado AS estado_trabajo,c.activa AS cohorte_activa,
                    m.requiere_tutor,m.requiere_informes,m.requiere_asistencia,m.asistencia_minima_pct,
                    m.requiere_mdg1,m.requiere_mdg2,m.requiere_informe_final,
                    m.requiere_tribunal,m.requiere_defensa,m.max_defensas,m.avance_requerido_defensa,
                    m.impide_tutor_tribunal,m.miembros_minimos_tribunal
             FROM mg_trabajos w INNER JOIN mg_modalidades m ON m.id_modalidad=w.id_modalidad
             INNER JOIN mg_cohortes c ON c.id_cohorte=w.id_cohorte WHERE w.id_trabajo=:id LIMIT 1'
        );
        $query->execute(['id'=>$workId]);$rules=$query->fetch(PDO::FETCH_ASSOC);
        if(!$rules){return [];}
        $items=[];
        $count=Database::connection()->prepare('SELECT COUNT(*) FROM mg_trabajo_integrantes ti INNER JOIN mg_inscripciones i ON i.id_inscripcion=ti.id_inscripcion WHERE ti.id_trabajo=:work AND ti.estado="activo" AND i.estado="activa"');
        $count->execute(['work'=>$workId]);$memberCount=(int)$count->fetchColumn();
        $items[]=['key'=>'integrantes','nombre'=>'Trabajo con integrantes activos','ok'=>$memberCount>0&&$rules['estado_trabajo']==='activo','detalle'=>$memberCount.' estudiante(s) activo(s)'];
        if((int)$rules['requiere_tutor']===1){
            $q=Database::connection()->prepare('SELECT CONCAT(u.nombre," ",u.apellido) FROM mg_asignaciones_tutor a INNER JOIN tutores t ON t.id_tutor=a.id_tutor INNER JOIN usuarios u ON u.id_usuario=t.id_usuario AND u.estado="activo" WHERE a.id_trabajo=:work AND a.estado="activa" LIMIT 1');
            $q->execute(['work'=>$workId]);$tutor=$q->fetchColumn();
            $items[]=['key'=>'tutor','nombre'=>'Tutor asignado','ok'=>(bool)$tutor,'detalle'=>$tutor?:'Sin asignación activa'];
        }
        foreach(['mdg1'=>'requiere_mdg1','mdg2'=>'requiere_mdg2'] as $stage=>$flag){
            if((int)$rules[$flag]!==1){continue;}
            $q=Database::connection()->prepare('SELECT estado FROM mg_resultados_etapa WHERE id_trabajo=:work AND etapa=:stage LIMIT 1');$q->execute(['work'=>$workId,'stage'=>$stage]);$state=$q->fetchColumn();
            $items[]=['key'=>$stage,'nombre'=>strtoupper($stage).' aprobado','ok'=>$state==='aprobado','detalle'=>$state?:'Sin resultado registrado'];
        }
        if((int)$rules['requiere_informes']===1){
            $q=Database::connection()->prepare('SELECT COUNT(*) total,COUNT(CASE WHEN sh.estado="aprobado" AND ri.estado="aprobado" THEN 1 END) aprobados FROM mg_seguimiento_hitos sh INNER JOIN mg_calendario h ON h.id_hito=sh.id_hito AND h.estado="activo" AND h.tipo="informe" LEFT JOIN mg_informes_grado ri ON ri.id_seguimiento=sh.id_seguimiento WHERE sh.id_trabajo=:work');
            $q->execute(['work'=>$workId]);$n=$q->fetch(PDO::FETCH_ASSOC);
            $items[]=['key'=>'informes','nombre'=>'Informes requeridos aprobados','ok'=>(int)$n['total']>0&&(int)$n['total']===(int)$n['aprobados'],'detalle'=>(int)$n['aprobados'].' / '.(int)$n['total'].' aprobados'];
        }
        if((int)$rules['requiere_informe_final']===1){
            $q=Database::connection()->prepare('SELECT COUNT(*) total,COUNT(CASE WHEN sh.estado="aprobado" AND ri.estado="aprobado" THEN 1 END) aprobados FROM mg_seguimiento_hitos sh INNER JOIN mg_calendario h ON h.id_hito=sh.id_hito AND h.estado="activo" AND h.tipo="informe" LEFT JOIN mg_informes_grado ri ON ri.id_seguimiento=sh.id_seguimiento WHERE sh.id_trabajo=:work AND LOWER(h.nombre) LIKE "%final%"');
            $q->execute(['work'=>$workId]);$n=$q->fetch(PDO::FETCH_ASSOC);
            $items[]=['key'=>'informe_final','nombre'=>'Informe final aprobado','ok'=>(int)$n['total']>0&&(int)$n['total']===(int)$n['aprobados'],'detalle'=>(int)$n['aprobados'].' / '.(int)$n['total'].' aprobados'];
        }
        if((int)$rules['requiere_asistencia']===1){
            $attendance=$this->attendanceByStudent($workId);$minimum=$rules['asistencia_minima_pct']===null?null:(float)$rules['asistencia_minima_pct'];
            $ok=$minimum!==null&&count($attendance)===$memberCount&&$memberCount>0;
            foreach($attendance as $row){if((int)$row['total']<1||(float)$row['porcentaje']<$minimum){$ok=false;}}
            $items[]=['key'=>'asistencia','nombre'=>'Asistencia mínima','ok'=>$ok,'detalle'=>$minimum===null?'Falta configurar el porcentaje mínimo.':'Mínimo '.$minimum.'%; '.$this->attendanceDetail($attendance)];
        }
        if($rules['avance_requerido_defensa']!==null){
            $q=Database::connection()->prepare('SELECT COUNT(avance_real_pct) total,AVG(avance_real_pct) promedio FROM mg_seguimiento_hitos WHERE id_trabajo=:work');$q->execute(['work'=>$workId]);$n=$q->fetch(PDO::FETCH_ASSOC);$target=(float)$rules['avance_requerido_defensa'];
            $items[]=['key'=>'avance','nombre'=>'Avance requerido','ok'=>(int)$n['total']>0&&(float)$n['promedio']>=$target,'detalle'=>$n['promedio']===null?'Sin avances registrados':number_format((float)$n['promedio'],2).'% / '.$target.'%'];
        }
        if((int)$rules['requiere_tribunal']===1){
            $tribunal=$this->currentTribunalSummary($workId);$min=max(2,(int)($rules['miembros_minimos_tribunal']??2));$ok=$tribunal&&(int)$tribunal['total_miembros']>=$min&&(int)$tribunal['presidentes']===1;
            if($ok&&(int)$rules['impide_tutor_tribunal']===1&&$tribunal['tutor_id_usuario']!==null){$q=Database::connection()->prepare('SELECT 1 FROM mg_tribunal_miembros WHERE id_tribunal=:id AND id_usuario=:user LIMIT 1');$q->execute(['id'=>$tribunal['id_tribunal'],'user'=>(int)$tribunal['tutor_id_usuario']]);if($q->fetchColumn()){$ok=false;}}
            $items[]=['key'=>'tribunal','nombre'=>'Tribunal conformado','ok'=>(bool)$ok,'detalle'=>$tribunal?(int)$tribunal['total_miembros'].' miembro(s), '.(int)$tribunal['presidentes'].' presidente(s)':'Sin tribunal activo'];
        }
        return $items;
    }

    private function attendanceByStudent(int $workId): array
    {
        $q=Database::connection()->prepare('SELECT i.id_estudiante,COUNT(CASE WHEN a.estado IN ("presente","ausente") THEN 1 END) total,COUNT(CASE WHEN a.estado="presente" THEN 1 END) atendidas,CASE WHEN COUNT(CASE WHEN a.estado IN ("presente","ausente") THEN 1 END)=0 THEN 0 ELSE 100*COUNT(CASE WHEN a.estado="presente" THEN 1 END)/COUNT(CASE WHEN a.estado IN ("presente","ausente") THEN 1 END) END porcentaje FROM mg_trabajo_integrantes ti INNER JOIN mg_inscripciones i ON i.id_inscripcion=ti.id_inscripcion AND i.estado="activa" LEFT JOIN mg_sesiones_seguimiento s ON s.id_trabajo=:session_work AND s.estado<>"cancelada" LEFT JOIN mg_asistencias a ON a.id_sesion=s.id_sesion AND a.id_estudiante=i.id_estudiante WHERE ti.id_trabajo=:member_work AND ti.estado="activo" GROUP BY i.id_estudiante');
        $q->execute(['session_work'=>$workId,'member_work'=>$workId]);return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    private function attendanceDetail(array $rows): string
    {
        return $rows?implode(', ',array_map(static fn(array $row):string=>number_format((float)$row['porcentaje'],2).'%', $rows)):'Sin integrantes activos.';
    }

    public function tribunalCandidates(): array
    {
        return Database::connection()->query('SELECT u.id_usuario,u.nombre,u.apellido,u.correo,r.nombre_rol,t.id_tutor FROM usuarios u INNER JOIN roles r ON r.id_rol=u.id_rol AND r.nombre_rol IN ("tutor","coordinador_mg","auxiliar_mg") LEFT JOIN tutores t ON t.id_usuario=u.id_usuario WHERE u.estado="activo" ORDER BY u.apellido,u.nombre')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function replaceTribunal(int $workId,int $adminId,int $presidentId,array $memberIds,string $note): void
    {
        $note=trim($note);if($presidentId<1||mb_strlen($note)>1000){throw new RuntimeException('Seleccione presidente y observación de hasta 1.000 caracteres.');}
        $members=array_values(array_unique(array_filter(array_map('intval',$memberIds),static fn(int $id):bool=>$id>0)));$members=array_values(array_diff($members,[$presidentId]));$selected=array_merge([$presidentId],$members);
        if(count($selected)<2){throw new RuntimeException('El tribunal debe tener un presidente y al menos un miembro.');}
        $pdo=Database::connection();$pdo->beginTransaction();
        try{
            $q=$pdo->prepare('SELECT w.estado,m.requiere_tribunal,m.impide_tutor_tribunal,m.miembros_minimos_tribunal FROM mg_trabajos w INNER JOIN mg_modalidades m ON m.id_modalidad=w.id_modalidad WHERE w.id_trabajo=:work FOR UPDATE');$q->execute(['work'=>$workId]);$rules=$q->fetch(PDO::FETCH_ASSOC);
            if(!$rules||$rules['estado']!=='activo'){throw new RuntimeException('Seleccione un trabajo activo.');}
            $minimum=max(2,(int)($rules['miembros_minimos_tribunal']??2));if(count($selected)<$minimum){throw new RuntimeException("Esta modalidad requiere al menos {$minimum} integrantes.");}
            $tutor=$pdo->prepare('SELECT t.id_usuario FROM mg_asignaciones_tutor a INNER JOIN tutores t ON t.id_tutor=a.id_tutor WHERE a.id_trabajo=:work AND a.estado="activa" LIMIT 1');$tutor->execute(['work'=>$workId]);$tutorUser=$tutor->fetchColumn();
            if((int)$rules['impide_tutor_tribunal']===1&&$tutorUser&&in_array((int)$tutorUser,$selected,true)){throw new RuntimeException('La modalidad impide incluir al tutor como miembro del tribunal.');}
            $marks=implode(',',array_fill(0,count($selected),'?'));$valid=$pdo->prepare("SELECT id_usuario FROM usuarios u INNER JOIN roles r ON r.id_rol=u.id_rol WHERE u.estado='activo' AND r.nombre_rol IN ('tutor','coordinador_mg','auxiliar_mg') AND u.id_usuario IN ($marks) FOR UPDATE");$valid->execute($selected);
            if(count(array_map('intval',$valid->fetchAll(PDO::FETCH_COLUMN)))!==count($selected)){throw new RuntimeException('Todos los miembros deben ser usuarios activos elegibles.');}
            $current=$pdo->prepare('SELECT id_tribunal FROM mg_tribunales WHERE id_trabajo=:work AND estado="activo" FOR UPDATE');$current->execute(['work'=>$workId]);$old=(int)$current->fetchColumn();
            if($old){$pdo->prepare('UPDATE mg_tribunales SET estado="reemplazado",finalizado_en=CURRENT_TIMESTAMP WHERE id_tribunal=:id')->execute(['id'=>$old]);}
            $pdo->prepare('INSERT INTO mg_tribunales (id_trabajo,designado_por,observacion) VALUES (:work,:admin,:note)')->execute(['work'=>$workId,'admin'=>$adminId,'note'=>$note!==''?$note:null]);$tribunal=(int)$pdo->lastInsertId();
            $save=$pdo->prepare('INSERT INTO mg_tribunal_miembros (id_tribunal,id_usuario,rol) VALUES (:tribunal,:user,:role)');$save->execute(['tribunal'=>$tribunal,'user'=>$presidentId,'role'=>'presidente']);
            foreach($members as $id){$save->execute(['tribunal'=>$tribunal,'user'=>$id,'role'=>'miembro']);}
            $this->auditRequests($pdo,$workId,$adminId,$old?'tribunal_reemplazado':'tribunal_asignado','Tribunal asignado'.($note!==''?' · '.$note:''));
            $pdo->commit();
        }catch(Throwable $exception){if($pdo->inTransaction()){$pdo->rollBack();}throw $exception;}
    }

    public function scheduleDefense(int $workId,int $adminId,string $dateTime,string $place,string $note): int
    {
        $dateTime=trim($dateTime);$place=trim($place);$note=trim($note);
        if(!$this->validDateTime($dateTime)||strtotime($dateTime)<=time()||mb_strlen($place)>255||mb_strlen($note)>5000){throw new RuntimeException('Ingrese una fecha/hora futura y ubicación/observación dentro del límite.');}
        $pdo=Database::connection();$pdo->beginTransaction();
        try{
            $q=$pdo->prepare('SELECT w.estado,m.requiere_defensa,m.requiere_tribunal,m.max_defensas FROM mg_trabajos w INNER JOIN mg_modalidades m ON m.id_modalidad=w.id_modalidad WHERE w.id_trabajo=:work FOR UPDATE');$q->execute(['work'=>$workId]);$rule=$q->fetch(PDO::FETCH_ASSOC);
            if(!$rule||$rule['estado']!=='activo'||(int)$rule['requiere_defensa']!==1){throw new RuntimeException('La modalidad no requiere defensa o el trabajo no está activo.');}
            $this->requireReady($this->checklist($workId));
            $pending=$pdo->prepare('SELECT id_defensa FROM mg_defensas WHERE id_trabajo=:work AND estado="programada" FOR UPDATE');$pending->execute(['work'=>$workId]);if($pending->fetchColumn()){throw new RuntimeException('Ya hay una defensa pendiente; reprográmela desde ese registro.');}
            $attempts=$pdo->prepare('SELECT COUNT(*) FROM mg_defensas WHERE id_trabajo=:work AND estado<>"cancelada"');$attempts->execute(['work'=>$workId]);$attemptCount=(int)$attempts->fetchColumn();
            if($rule['max_defensas']!==null&&(int)$rule['max_defensas']>0&&$attemptCount>=(int)$rule['max_defensas']){throw new RuntimeException('Se alcanzó el máximo de defensas configurado.');}
            $tribunal=$this->currentTribunal($workId);if((int)$rule['requiere_tribunal']===1&&(!$tribunal||(int)$tribunal['presidentes']!==1)){throw new RuntimeException('Asigne un tribunal válido antes de programar defensa.');}
            $num=(int)$pdo->query('SELECT COALESCE(MAX(numero_defensa),0)+1 FROM mg_defensas WHERE id_trabajo='.(int)$workId)->fetchColumn();
            $pdo->prepare('INSERT INTO mg_defensas (id_trabajo,numero_defensa,id_tribunal,fecha_hora,ubicacion,programada_por) VALUES (:work,:num,:tribunal,:when,:place,:admin)')->execute(['work'=>$workId,'num'=>$num,'tribunal'=>$tribunal?(int)$tribunal['id_tribunal']:null,'when'=>$this->sqlDateTime($dateTime),'place'=>$place!==''?$place:null,'admin'=>$adminId]);
            $id=(int)$pdo->lastInsertId();$this->defenseEvent($pdo,$id,'programada',null,$this->sqlDateTime($dateTime),null,'programada',null,null,$adminId,$note);
            $this->auditRequests($pdo,$workId,$adminId,'defensa_programada',"Defensa #{$num} · ".$this->sqlDateTime($dateTime).($note!==''?' · '.$note:''));
            $pdo->commit();return $id;
        }catch(Throwable $exception){if($pdo->inTransaction()){$pdo->rollBack();}throw $exception;}
    }

    public function defenseNumber(int $defenseId): int
    {
        $statement = Database::connection()->prepare('SELECT numero_defensa FROM mg_defensas WHERE id_defensa=:id LIMIT 1');
        $statement->execute(['id' => $defenseId]);
        return (int) $statement->fetchColumn();
    }

    public function updateDefense(int $defenseId,int $adminId,string $action,array $input): void
    {
        $pdo=Database::connection();$pdo->beginTransaction();
        try{
            $q=$pdo->prepare('SELECT * FROM mg_defensas WHERE id_defensa=:id FOR UPDATE');$q->execute(['id'=>$defenseId]);$defense=$q->fetch(PDO::FETCH_ASSOC);
            if(!$defense||$defense['estado']!=='programada'){throw new RuntimeException('La defensa ya se realizó o canceló.');}
            if($action==='reprogramar'){
                $raw=trim((string)($input['fecha_hora']??''));$place=trim((string)($input['ubicacion']??''));$note=trim((string)($input['observaciones']??''));
                if(!$this->validDateTime($raw)||strtotime($raw)<=time()||mb_strlen($place)>255||mb_strlen($note)>5000){throw new RuntimeException('Ingrese fecha/hora futura y observaciones válidas.');}
                $date=$this->sqlDateTime($raw);$pdo->prepare('UPDATE mg_defensas SET fecha_hora=:date,ubicacion=:place WHERE id_defensa=:id')->execute(['date'=>$date,'place'=>$place!==''?$place:null,'id'=>$defenseId]);
                $this->defenseEvent($pdo,$defenseId,'reprogramada',$defense['fecha_hora'],$date,'programada','programada',null,null,$adminId,$note);
                $this->auditRequests($pdo,(int)$defense['id_trabajo'],$adminId,'defensa_reprogramada','Defensa reprogramada · '.$date.($note!==''?' · '.$note:''));
            }elseif($action==='resultado'){
                $result=(string)($input['resultado']??'');$grade=trim((string)($input['nota']??''));$note=trim((string)($input['observaciones']??''));
                if(!in_array($result,['aprobado','observado','reprobado'],true)||mb_strlen($note)>5000){throw new RuntimeException('Seleccione resultado válido y observaciones de hasta 5.000 caracteres.');}
                $score=null;if($grade!==''){if(!is_numeric($grade)||(float)$grade<0||(float)$grade>100){throw new RuntimeException('La nota debe estar entre 0 y 100.');}$score=number_format((float)$grade,2,'.','');}
                $pdo->prepare('UPDATE mg_defensas SET estado="realizada",resultado=:result,nota=:score,observaciones=:note WHERE id_defensa=:id')->execute(['result'=>$result,'score'=>$score,'note'=>$note!==''?$note:null,'id'=>$defenseId]);
                $this->defenseEvent($pdo,$defenseId,'resultado_registrado',$defense['fecha_hora'],$defense['fecha_hora'],'programada','realizada',$result,$score,$adminId,$note);
                $this->auditRequests($pdo,(int)$defense['id_trabajo'],$adminId,'resultado_defensa',strtoupper($result).($score!==null?' · '.$score:'').($note!==''?' · '.$note:''));
            }elseif($action==='cancelar'){
                $note=trim((string)($input['observaciones']??''));if($note===''){throw new RuntimeException('Indique el motivo de cancelación.');}
                $pdo->prepare('UPDATE mg_defensas SET estado="cancelada",observaciones=:note WHERE id_defensa=:id')->execute(['note'=>$note,'id'=>$defenseId]);
                $this->defenseEvent($pdo,$defenseId,'cancelada',$defense['fecha_hora'],$defense['fecha_hora'],'programada','cancelada',null,null,$adminId,$note);
                $this->auditRequests($pdo,(int)$defense['id_trabajo'],$adminId,'defensa_cancelada',$note);
            }else{throw new RuntimeException('Acción de defensa no válida.');}
            $pdo->commit();
        }catch(Throwable $exception){if($pdo->inTransaction()){$pdo->rollBack();}throw $exception;}
    }

    public function close(int $workId,int $adminId,string $outcome,string $note): void
    {
        $note=trim($note);if(!in_array($outcome,['aprobado','reprobado'],true)||mb_strlen($note)>5000){throw new RuntimeException('Seleccione resultado final y observaciones válidos.');}
        $pdo=Database::connection();$pdo->beginTransaction();
        try{
            $q=$pdo->prepare('SELECT w.estado,m.requiere_defensa,m.max_defensas FROM mg_trabajos w INNER JOIN mg_modalidades m ON m.id_modalidad=w.id_modalidad WHERE w.id_trabajo=:work FOR UPDATE');$q->execute(['work'=>$workId]);$work=$q->fetch(PDO::FETCH_ASSOC);
            if(!$work||$work['estado']!=='activo'){throw new RuntimeException('El trabajo no está activo.');}
            $exists=$pdo->prepare('SELECT id_cierre FROM mg_cierres WHERE id_trabajo=:work FOR UPDATE');$exists->execute(['work'=>$workId]);if($exists->fetchColumn()){throw new RuntimeException('El trabajo ya está cerrado.');}
            $this->requireReady($this->checklist($workId));
            if((int)$work['requiere_defensa']===1){
                $latest=$pdo->prepare('SELECT estado,resultado FROM mg_defensas WHERE id_trabajo=:work AND estado<>"cancelada" ORDER BY numero_defensa DESC LIMIT 1 FOR UPDATE');$latest->execute(['work'=>$workId]);$defense=$latest->fetch(PDO::FETCH_ASSOC);
                $terminal=$defense&&$defense['estado']==='realizada'&&in_array($defense['resultado'],['aprobado','reprobado'],true);
                $observedAtLimit=false;
                if($defense&&$defense['estado']==='realizada'&&$defense['resultado']==='observado'&&$work['max_defensas']!==null){
                    $attempts=$pdo->prepare('SELECT COUNT(*) FROM mg_defensas WHERE id_trabajo=:work AND estado<>"cancelada"');$attempts->execute(['work'=>$workId]);
                    $observedAtLimit=(int)$attempts->fetchColumn()>=(int)$work['max_defensas'];
                }
                if(!$terminal&&!$observedAtLimit){throw new RuntimeException('Falta un resultado terminal de defensa o todavía quedan intentos permitidos.');}
                $expected=$observedAtLimit?'reprobado':$defense['resultado'];
                if($outcome!==$expected){throw new RuntimeException('El resultado de cierre debe coincidir con la última defensa o con el máximo de intentos observados.');}
            }
            $pdo->prepare('INSERT INTO mg_cierres (id_trabajo,resultado,observaciones,cerrado_por) VALUES (:work,:outcome,:note,:admin)')->execute(['work'=>$workId,'outcome'=>$outcome,'note'=>$note!==''?$note:null,'admin'=>$adminId]);
            $pdo->prepare('UPDATE mg_trabajos SET estado="finalizado" WHERE id_trabajo=:work')->execute(['work'=>$workId]);
            $pdo->prepare('UPDATE mg_inscripciones SET estado="finalizada" WHERE id_estudiante IN (SELECT id_estudiante FROM mg_trabajo_integrantes WHERE id_trabajo=:work AND estado="activo") AND estado="activa"')->execute(['work'=>$workId]);
            $pdo->prepare('UPDATE mg_trabajo_integrantes SET estado="finalizado" WHERE id_trabajo=:work AND estado="activo"')->execute(['work'=>$workId]);
            $pdo->prepare('UPDATE mg_asignaciones_tutor SET estado="finalizada",fecha_fin=CURRENT_TIMESTAMP WHERE id_trabajo=:work AND estado="activa"')->execute(['work'=>$workId]);
            $this->auditRequests($pdo,$workId,$adminId,'proceso_cerrado','Cierre '.$outcome.($note!==''?' · '.$note:''));
            $pdo->commit();
        }catch(Throwable $exception){if($pdo->inTransaction()){$pdo->rollBack();}throw $exception;}
    }

    private function currentTribunalSummary(int $workId): ?array
    {
        $q=Database::connection()->prepare('SELECT t.id_tribunal,COUNT(tm.id_miembro) total_miembros,COUNT(CASE WHEN tm.rol="presidente" THEN 1 END) presidentes,at.id_usuario tutor_id_usuario FROM mg_tribunales t LEFT JOIN mg_tribunal_miembros tm ON tm.id_tribunal=t.id_tribunal LEFT JOIN mg_asignaciones_tutor a ON a.id_trabajo=t.id_trabajo AND a.estado="activa" LEFT JOIN tutores at ON at.id_tutor=a.id_tutor WHERE t.id_trabajo=:work AND t.estado="activo" GROUP BY t.id_tribunal,at.id_usuario LIMIT 1');
        $q->execute(['work'=>$workId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
    }

    private function tribunals(int $workId): array
    {
        $q=Database::connection()->prepare('SELECT t.*,CONCAT(u.nombre," ",u.apellido) designado_por_nombre FROM mg_tribunales t INNER JOIN usuarios u ON u.id_usuario=t.designado_por WHERE t.id_trabajo=:work ORDER BY t.designado_en DESC');$q->execute(['work'=>$workId]);return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    private function tribunalMembers(int $tribunalId): array
    {
        $q=Database::connection()->prepare('SELECT tm.id_usuario,tm.rol,u.nombre,u.apellido,u.correo,r.nombre_rol FROM mg_tribunal_miembros tm INNER JOIN usuarios u ON u.id_usuario=tm.id_usuario INNER JOIN roles r ON r.id_rol=u.id_rol WHERE tm.id_tribunal=:id ORDER BY FIELD(tm.rol,"presidente","miembro"),u.apellido,u.nombre');$q->execute(['id'=>$tribunalId]);return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    private function defenses(int $workId): array
    {
        $q=Database::connection()->prepare('SELECT d.*,CONCAT(u.nombre," ",u.apellido) programada_por_nombre FROM mg_defensas d INNER JOIN usuarios u ON u.id_usuario=d.programada_por WHERE d.id_trabajo=:work ORDER BY d.numero_defensa DESC');$q->execute(['work'=>$workId]);$rows=$q->fetchAll(PDO::FETCH_ASSOC);foreach($rows as &$row){$row['historial']=$this->defenseHistory((int)$row['id_defensa']);}unset($row);return $rows;
    }

    private function defenseHistory(int $defenseId): array
    {
        $q=Database::connection()->prepare('SELECT h.*,CONCAT(u.nombre," ",u.apellido) actor FROM mg_defensa_historial h INNER JOIN usuarios u ON u.id_usuario=h.registrado_por WHERE h.id_defensa=:id ORDER BY h.registrado_en,h.id_evento');$q->execute(['id'=>$defenseId]);return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    private function closure(int $workId): ?array
    {
        $q=Database::connection()->prepare('SELECT c.*,CONCAT(u.nombre," ",u.apellido) cerrado_por_nombre FROM mg_cierres c INNER JOIN usuarios u ON u.id_usuario=c.cerrado_por WHERE c.id_trabajo=:work LIMIT 1');$q->execute(['work'=>$workId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
    }

    private function requireReady(array $items): void
    {
        $missing=array_values(array_filter($items,static fn(array $item):bool=>!$item['ok']));
        if($missing){throw new RuntimeException('Requisitos pendientes: '.implode('; ',array_map(static fn(array $item):string=>$item['nombre'],$missing)).'.');}
    }

    private function currentTribunal(int $workId): ?array
    {
        $q=Database::connection()->prepare('SELECT t.id_tribunal,COUNT(tm.id_miembro) miembros,COUNT(CASE WHEN tm.rol="presidente" THEN 1 END) presidentes FROM mg_tribunales t LEFT JOIN mg_tribunal_miembros tm ON tm.id_tribunal=t.id_tribunal WHERE t.id_trabajo=:work AND t.estado="activo" GROUP BY t.id_tribunal LIMIT 1');$q->execute(['work'=>$workId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
    }

    private function auditRequests(PDO $pdo,int $workId,int $actorId,string $action,string $comment): void
    {
        $q=$pdo->prepare('SELECT DISTINCT i.id_solicitud,e.id_usuario FROM mg_trabajo_integrantes ti INNER JOIN mg_inscripciones i ON i.id_inscripcion=ti.id_inscripcion INNER JOIN estudiantes e ON e.id_estudiante=i.id_estudiante WHERE ti.id_trabajo=:work');$q->execute(['work'=>$workId]);
        foreach($q->fetchAll(PDO::FETCH_ASSOC) as $request){
            $pdo->prepare('INSERT INTO mg_solicitud_historial (id_solicitud,accion,estado_anterior,estado_nuevo,id_actor,comentario,visible_estudiante) VALUES (:request,:action,"aprobada","aprobada",:actor,:comment,1)')->execute(['request'=>(int)$request['id_solicitud'],'action'=>$action,'actor'=>$actorId,'comment'=>$comment]);
            (new Notificacion())->add($pdo,(int)$request['id_usuario'],'mg_'.$action,'Actualización de Modalidad de Grado',mb_substr($comment,0,1000),'modalidades-grado/mi-solicitud.php?id='.(int)$request['id_solicitud'],'mg-event:'.(int)$request['id_solicitud'].':'.(int)$pdo->lastInsertId());
        }
    }

    private function defenseEvent(PDO $pdo,int $defenseId,string $action,?string $oldDate,?string $newDate,?string $oldState,string $newState,?string $result,?string $grade,int $actorId,string $note): void
    {
        $pdo->prepare('INSERT INTO mg_defensa_historial (id_defensa,accion,fecha_anterior,fecha_nueva,estado_anterior,estado_nuevo,resultado,nota,observaciones,registrado_por) VALUES (:id,:action,:old_date,:new_date,:old_state,:new_state,:result,:grade,:note,:actor)')
            ->execute(['id'=>$defenseId,'action'=>$action,'old_date'=>$oldDate,'new_date'=>$newDate,'old_state'=>$oldState,'new_state'=>$newState,'result'=>$result,'grade'=>$grade,'note'=>$note!==''?$note:null,'actor'=>$actorId]);
    }

    private function validDateTime(string $value): bool
    {
        $date=DateTimeImmutable::createFromFormat('Y-m-d\TH:i',$value);return $date!==false&&$date->format('Y-m-d\TH:i')===$value;
    }

    private function sqlDateTime(string $value): string
    {
        return str_replace('T',' ',$value).':00';
    }
}
