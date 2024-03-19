<?php
	require_once ('../controller/webservice_init.php');
    // require("../../PHPMailer-master/src/PHPMailer.php");
    // require("../../PHPMailer-master/src/SMTP.php");
	$host = "https://".$_SERVER['HTTP_HOST'];
	// require 'PHPMailer/PHPMailerAutoload.php';
	$urlImage = "https://portal.espertibrasil.com.br/images/";
	$intReturn = 0;
	$data = null;
	if ($_SERVER['REQUEST_METHOD'] === "POST") {

		if (!isset($_SERVER['PHP_AUTH_USER']))
			$intReturn = -2;			
		else 
		{
			if(Utility::AuthorizationHeaderAPI($_SERVER['PHP_AUTH_USER'] , $_SERVER['PHP_AUTH_PW']))
			{	
                $route = explode('email',$_SERVER["REDIRECT_URL"]);
				$route = trim($route[1],"/");
				switch($route){
                    case 'sendCobranca':
						$inputJSON = file_get_contents('php://input');
						$objectJson = json_decode($inputJSON);

						$nomePeritoArray =explode(" ",  strtolower($objectJson->nome_perito));
						$nomePerito = ucfirst($nomePeritoArray[0]);

						$boleto = true;
						$relatorioCompleto = $objectJson->relatorioCompleto;

						$url = "'https://portal.espertibrasil.com.br/images/ppEsperti.png'";
						$assunto = "Email de cobrança Buscador Honorários";
						if($objectJson->vitalicio){ // Cliente Vitalicio = Not Boleto
							$boleto = false;
							$assunto = "Movimentação de status de recebimento";

						}

						$htmlBody = BodyTextEmailRelorio($objectJson,$relatorioCompleto,$boleto);
						$html = email($url, $nomePerito, $htmlBody);

						$body = $html;
						$mail = Utility::SendMail($assunto, $body);

						$mail = emailAddres($mail,$objectJson);
						$mail->AddEmbeddedImage($host."/images/ppEsperti.png", 'logo_esperti');
						$mail->addCC("contato@espertibrasil.com.br");

						if($objectJson->hasAlteracoes && !$relatorioCompleto){
							$mail->AddStringAttachment(base64_decode($objectJson->archive_relatorio), 'Relatorio Perito_' . $objectJson->nome_perito ."_".date("Y-m").'.xlsx', 'base64', 'application/vnd.ms-excel');
						}

						if($relatorioCompleto){
							$mail->AddStringAttachment(base64_decode($objectJson->archive_relatorio_R), 'Relatorio_Recebimento_' . $objectJson->nome_perito ."_".date("Y-m").'.xlsx', 'base64', 'application/vnd.ms-excel');
							$mail->AddStringAttachment(base64_decode($objectJson->archive_relatorio_C), 'Relatorio_Buscador_' . $objectJson->nome_perito ."_".date("Y-m").'.xlsx', 'base64', 'application/vnd.ms-excel');
						}
						
						if(!$objectJson->vitalicio){  // Cliente Não Vitalicia = Gerar boleto = Email bolet Vencimento
							$mail->AddStringAttachment(base64_decode($objectJson->string_boleto), 'Boleto.pdf', 'base64', 'application/pdf');
						}
						

						try {
							if($mail->send()){
								$data["status"] = 200;
								$data["msg"] = "Email Enviado com Sucesso!";
							}else{
								$data["status"] = 201;
								$data["tipoErro"] = "Erro ao Enviar Email!";
								$data["msg"] = "Não foi possível enviar o e-mail de Alerta de Vencimento do Perito ".$nomePerito;
							}
						} catch (phpmailerException $e) {
							echo $e->errorMessage(); //Pretty error messages from PHPMailer
						} catch (Exception $e) {
							echo $e->getMessage(); //Boring error messages from anything else!
						}
                    break;
				
					case 'sendRelatorioReduzido':
						$inputJSON = file_get_contents('php://input');
						$objectJson = json_decode($inputJSON);

						$nomePeritoArray =explode(" ",  strtolower($objectJson->nome_perito));
						$nomePerito = ucfirst($nomePeritoArray[0]);

						$boleto = false;
						$relatorioCompleto =false;

						$url = "'https://portal.espertibrasil.com.br/images/ppEsperti.png'";

						$htmlBody = BodyTextEmailRelorio($objectJson,$relatorioCompleto,$boleto);
						$html = email($url, $nomePerito, $htmlBody);

						$assunto = "Movimentação de status de recebimento";
						$body = $html;
						$mail = Utility::SendMail($assunto, $body);

						$mail = emailAddres($mail,$objectJson);

						$mail->AddEmbeddedImage($host."/images/ppEsperti.png", 'logo_esperti');
						$mail->addCC("contato@espertibrasil.com.br");
						
						if($objectJson->hasAlteracoes){  // Perito onde não ocorreu nenhuma alteração no periodo
							$mail->AddStringAttachment(base64_decode($objectJson->archive_relatorioReduzido), 'Relatorio Alteracao_' . $objectJson->nome_perito ."_".date("Y-m").'.xlsx', 'base64', 'application/vnd.ms-excel');
						}
						
						try {
							if($mail->send()){
								$data["status"] = 200;
								$data["msg"] = "Email Enviado com Sucesso!";
							}else{
								$data["htmlBody"] = $objectJson;
								$data["status"] = 201;
								$data["tipoErro"] = "Erro ao Enviar Email!";
								$data["msg"] = "Não foi possível enviar o e-mail do Perito ".$nomePerito;
							}
						} catch (phpmailerException $e) {
							echo $e->errorMessage(); //Pretty error messages from PHPMailer
						} catch (Exception $e) {
							echo $e->getMessage(); //Boring error messages from anything else!
						}
                    break;

					case 'sendReCobranca':
						$inputJSON = file_get_contents('php://input');
						$objectJson = json_decode($inputJSON);

						$nomePeritoArray =explode(" ",  strtolower($objectJson->nome_perito));
						$nomePerito = ucfirst($nomePeritoArray[0]);

						$boleto = true;

						$url = "'https://portal.espertibrasil.com.br/images/ppEsperti.png'";
						$assunto = "Reenvio de cobrança Buscador Honorários";

						$htmlBody = BodyTextEmailRelorio($objectJson,false,$boleto,true);
						$html = email($url, $nomePerito, $htmlBody);

						$body = $html;
						$mail = Utility::SendMail($assunto, $body);

						$mail = emailAddres($mail,$objectJson);
						$mail->AddEmbeddedImage($host."/images/ppEsperti.png", 'logo_esperti');
						$mail->addCC("contato@espertibrasil.com.br");

						$mail->AddStringAttachment(base64_decode($objectJson->string_boleto), 'Boleto.pdf', 'base64', 'application/pdf');
						
						try {
							if($mail->send()){
								$data["status"] = 200;
								$data["msg"] = "Email Enviado com Sucesso!";
							}else{
								$data["status"] = 201;
								$data["tipoErro"] = "Erro ao Enviar Email!";
								$data["msg"] = "Não foi possível enviar o e-mail de Alerta de Vencimento do Perito ".$nomePerito;
							}
						} catch (phpmailerException $e) {
							echo $e->errorMessage(); //Pretty error messages from PHPMailer
						} catch (Exception $e) {
							echo $e->getMessage(); //Boring error messages from anything else!
						}
                    break;
				
					case 'sendNewPassword':
						
						$inputJSON = file_get_contents('php://input');
						$objectJson = json_decode($inputJSON);

						$nomePeritoArray =explode(" ",  strtolower($objectJson->nome_perito));
						$nomePerito = ucfirst($nomePeritoArray[0]);
						$url = "'https://portal.espertibrasil.com.br/images/ppEsperti.png'";

						$htmlBody = '<td style="padding: 20px 0 30px 0;">
										<span style="font-size:14px;color:#555555">
											<p>
												Sua solicitação de recuperação da senha foi concluída. <br>
												Foi gerada uma senha randomica. <br><br>
												Nova senha: <b>'.$objectJson->new_password.'</b> <br><br>
							
												Seu login de acesso continua o mesmo: <b>'.$objectJson->user.'</b><br>
												<br>
												<img src="'.$urlImage.'imagem_email_mudarSenha.png" style="height:auto; width:100%; max-width:700px;">
												
											</p>
											<p>
											</p>
										</span>
									</td>';

						$html = email($url, $nomePerito, $htmlBody);

						$assunto = "Email de Recuperação de Senha";
						$body = $html;
						$mail = Utility::SendMail($assunto, $body);

						$mail = emailAddres($mail,$objectJson);

						$mail->AddEmbeddedImage("https://portal.espertibrasil.com.br/images/ppEsperti.png", 'logo_esperti');
				
						try {
							$mail->send();
						} catch (phpmailerException $e) {
							echo $e->errorMessage(); //Pretty error messages from PHPMailer
						} catch (Exception $e) {
							echo $e->getMessage(); //Boring error messages from anything else!
						}
					break;
					case 'sendCadastro':
						$inputJSON = file_get_contents('php://input');
						$objectJson = json_decode($inputJSON);

						$nomePeritoArray =explode(" ",  strtolower($objectJson->nome_perito));
						$nomePerito = ucfirst($nomePeritoArray[0]);
						$url = "'https://portal.espertibrasil.com.br/images/ppEsperti.png'";

						$textRelatorio = 'você receberá em até <b style="color:red">72 horas o relatório da sincronização pelo e-mail</b> ou acesse o <a href="https://portal.espertibrasil.com.br">Buscador de Honorários da Esperti</a> utilizando o usuário e senha cadastrados.';
						if(property_exists($objectJson,'hasRecadastro'))
							if($objectJson->hasRecadastro)
								$textRelatorio = 'você receberá no <b style="color:red">próximo mês o relatório pelo e-mail</b> ou extraia pelo portal <a href="https://portal.espertibrasil.com.br">Buscador de Honorários,</a> utilizando o usuário e senha cadastrados.';
						
						$htmlBody = '<td style="padding: 20px 0 30px 0;">
										<h3 style="color:#555555">
											<p>
											O seu cadastro foi realizado com sucesso na plataforma Esperti! 
											</p>
											<p>

												<ul>
													<li>Baixe a extensão Esperti no Firefox abrindo o link abaixo pelo seu navegador:
														<a href="https://addons.mozilla.org/pt-BR/firefox/addon/esperti-brasil/"> https://addons.mozilla.org/pt-BR/firefox/addon/esperti-brasil/ </a>
													</li>
													<li>
														Quando abrir o link, você irá se deparar com essa imagem
													</li>													
												</ul>
											</p>
												<img src="'.$urlImage.'imageExtension1.png" style="height:auto; width:100%; max-width:700px;">
											<p>
												<ul>
													<li>Ordem de botões a serem clidados: <b style="color:black">Adicionar ao FireFox > Adicionar > Ok. </b> </li>
													<li>Acesse ao site do PJE para continuar a sincronização:</b> </li>
													<li>Quando acessar ao site do PJE você se irá se deparar com a imagem abaixo. Clique no botão verde “Sincronizar” para finalizar a sincronização do buscador. </b> </li>
												</ul>	
											</p>
												<img src="'.$urlImage.'imageExtension2.png" style="height:auto; width:100%; max-width:700px;">
											<p>
												<ul>
													<li>O Buscador de Honorários estará habilitado para utilização.</li>											
													<li>Apos a sincronização, '.$textRelatorio.'</li>
													<li>A Esperti é uma aliada do perito na elaboração de soluções para controle e recebimento de honorários.</li>
												</ul>	
											</p>
										</h3>
									</td>';

						$html = email($url, $nomePerito, $htmlBody);

						$assunto = "Email Para Sincronização";
						$body = $html;
						$mail = Utility::SendMail($assunto, $body);

						$mail = emailAddres($mail,$objectJson);
						$mail->AddEmbeddedImage($host."/images/ppEsperti.png", 'logo_esperti');
						$mail->addCC("contato@espertibrasil.com.br");
						$mail->addCC("comercial@espertibrasil.com.br");

						try {
							$mail->send();
						} catch (phpmailerException $e) {
							echo $e->errorMessage(); //Pretty error messages from PHPMailer
						} catch (Exception $e) {
							echo $e->getMessage(); //Boring error messages from anything else!
						}
					break;

					case 'sendCobrancaRecadastro':
						$inputJSON = file_get_contents('php://input');
						$objectJson = json_decode($inputJSON);

						$nomePeritoArray =explode(" ",  strtolower($objectJson->nome_perito));
						$nomePerito = ucfirst($nomePeritoArray[0]);
						$url = "'https://portal.espertibrasil.com.br/images/ppEsperti.png'";
						$htmlBody = '<td style="padding: 20px 0 30px 0;">
										<h3 style="color:#555555">
											<p>
												O boleto para reativar os serviços Esperti ja está disponivel e seu vencimento é para: <b style="color:black;">'.$objectJson->data_vencimento.'</b> e você pode conferir todos os detalhes no PDF anexo aqui no e-mail.
											</p>
											<p>
												Você pode realizar o pagamento em qualquer banco, na lotérica, nos canais digitais do seu banco (internet banking) ou app de pagamento.

											</p>
											<p>
												O processamento dos pagamentos pode levar até <b style="color:black;">2</b> dias úteis.
											<br>
											<p>
												Seu acesso será permitido após o processamento pagamento.
											</p>
										</h3>
									</td>';
						
						$html = email($url, $nomePerito, $htmlBody);

						$assunto = "Email de Recadastro Buscador Honorários";
						$body = $html;
                        $mail = Utility::SendMail($assunto, $body);
                        
						$mail = emailAddres($mail,$objectJson);
                        $mail->AddEmbeddedImage($host."/images/ppEsperti.png", 'logo_esperti');
						$mail->addCC("contato@espertibrasil.com.br");

                        $mail->AddStringAttachment(base64_decode($objectJson->string_boleto), 'Boleto.pdf', 'base64', 'application/pdf');
                        try {
							if($mail->send()){
								$data["status"] = 200;
								$data["msg"] = "Email Enviado com Sucesso!";
							}else{
								$data["status"] = 201;
								$data["tipoErro"] = "Erro ao Enviar Email!";
								$data["msg"] = "Não foi possível enviar o e-mail de erro!";
							}
						} catch (phpmailerException $e) {
							echo $e->errorMessage(); //Pretty error messages from PHPMailer
						} catch (Exception $e) {
							echo $e->getMessage(); //Boring error messages from anything else!
						}
					break;

					case 'remenberVencimento':
						$data = array();

						$inputJSON = file_get_contents('php://input');
						$objectJson = json_decode($inputJSON);

						$nomePeritoArray =explode(" ",  strtolower($objectJson->nome_perito));
						$nomePerito = ucfirst($nomePeritoArray[0]);
						$url = "'https://portal.espertibrasil.com.br/images/ppEsperti.png'";

						$htmlBody = '<td style="padding: 20px 0 30px 0;">
										<span style="font-size:14px;color:#555555">
											<p>
											Para auxiliá-lo na sua programação financeira, comunicamos que se aproxima a data de <b>vencimento do boleto</b> referente à sua assinatura mensal do 
											<a href="https://portal.espertibrasil.com.br">Buscador de Honorários da Esperti</a>.<br><br>
											 O pagamento da boleta é fundamental para manutenção dos acompanhamentos dos pagamentos e busca de honorários pendentes.
											</p>
											<p>
											</p>
										</span>
									</td>';

						$html = email($url, $nomePerito, $htmlBody);

						$assunto = "Lembrete de Vencimento";
						$body = $html;
						$mail = Utility::SendMail($assunto, $body);

						$mail = emailAddres($mail,$objectJson);
						$mail->addCC("contato@espertibrasil.com.br");

						$mail->AddEmbeddedImage("https://portal.espertibrasil.com.br/images/ppEsperti.png", 'logo_esperti');
						try {
							if($mail->send()){
								$data["status"] = 200;
								$data["msg"] = "Email Enviado com Sucesso!";
							}else{
								$data["status"] = 201;
								$data["tipoErro"] = "Erro ao Enviar Email!";
								$data["msg"] = "Não foi possível enviar o e-mail de Alerta de Vencimento do Perito ".$nomePerito;
							}
						} catch (phpmailerException $e) {
							echo $e->errorMessage(); //Pretty error messages from PHPMailer
						} catch (Exception $e) {
							echo $e->getMessage(); //Boring error messages from anything else!
						}
					break;

					case 'sendSugestao':
						$inputJSON = file_get_contents('php://input');
						$objectJson = json_decode($inputJSON);

						$nomePeritoArray =explode(" ",  strtolower($objectJson->nome_perito));
						$nomePerito = ucfirst($nomePeritoArray[0]);
						$url = "'https://portal.espertibrasil.com.br/images/ppEsperti.png'";
						$htmlBody = '<td style="padding: 20px 0 30px 0;">
										<h3 style="color:#555555">
											<p>
												'.$nomePerito.' enviou um email sobre '.$objectJson->assunto.'.
											</p>
											<p>
												'.$objectJson->message.'.	
											</p>
										</h3>
									</td>';
						
						$html = email($url, "Contato Esperti", $htmlBody);

						$assunto = $objectJson->assunto;
						$body = $html;
                        $mail = Utility::SendMail($assunto, $body);
                        
						$mail = emailAddres($mail,$objectJson);
                        $mail->AddEmbeddedImage($host."/images/ppEsperti.png", 'logo_esperti');
		
                        try {
							if($mail->send()){
								$data["status"] = 200;
								$data["msg"] = "Email Enviado com Sucesso!";
							}else{
								$data["status"] = 201;
								$data["tipoErro"] = "Erro ao Enviar Email!";
								$data["msg"] = "Não foi possível enviar o e-mail de erro!";
							}
						} catch (phpmailerException $e) {
							echo $e->errorMessage(); //Pretty error messages from PHPMailer
						} catch (Exception $e) {
							echo $e->getMessage(); //Boring error messages from anything else!
						}
					break;
					
					case 'sendIndicacao':
						$data = array();

						$inputJSON = file_get_contents('php://input');
						$objectJson = json_decode($inputJSON);

						$nomePeritoArray =explode(" ",  strtolower($objectJson->nome_peritoIndicando));
						$nomePerito = ucfirst($nomePeritoArray[0]);
						$url = "'https://portal.espertibrasil.com.br/images/ppEsperti.png'";

						$htmlBody = '<td style="padding: 20px 0 30px 0;">
										<span style="font-size:14px;color:#555555">
											<p>
											Tivemos a honra de recebê-lo como indicação através do <b>'.ucwords(strtolower($objectJson->nome_peritoIndicando)).'</b>, nosso cliente do <a href="https://portal.espertibrasil.com.br">Buscador de Honorários da Esperti</a>.
											O software é uma importante ferramenta no acompanhamento dos pagamentos de perícias e na identificação de possíveis pendência no recebimento de honorários.<br><br>

											Convidamos a acessar a plataforma da <a href="'.$objectJson->linkIndicacao.'">Esperti</a> e realizar o seu cadastro. 
										
											</p>
										</span>
									</td>';

						$assunto = "Você foi Indicado - Esperti";

						$html = emailAlert($url, $assunto, $htmlBody);

						$body = $html;
						$mail = Utility::SendMail($assunto, $body);

						$mail = emailAddres($mail,$objectJson);

						$mail->AddEmbeddedImage("https://portal.espertibrasil.com.br/images/ppEsperti.png", 'logo_esperti');
						try {
							if($mail->send()){
								$data["status"] = 200;
								$data["msg"] = "Email Enviado com Sucesso!";
							}else{
								$data["status"] = 201;
								$data["tipoErro"] = "Erro ao Enviar Email!";
								$data["msg"] = "Não foi possível enviar o e-mail de Alerta de Vencimento do Perito ".$nomePerito;
							}
						} catch (phpmailerException $e) {
							echo $e->errorMessage(); //Pretty error messages from PHPMailer
						} catch (Exception $e) {
							echo $e->getMessage(); //Boring error messages from anything else!
						}
					break;

					case 'recadastroBloqued':
						$data = array();

						$inputJSON = file_get_contents('php://input');
						$objectJson = json_decode($inputJSON);

						$nomePeritoArray =explode(" ",  strtolower($objectJson->nome_perito));
						$nomePerito = ucfirst($nomePeritoArray[0]);
						$url = "'https://portal.espertibrasil.com.br/images/ppEsperti.png'";
						$imagemPassoPasso = "'https://portal.espertibrasil.com.br/images/ppEsperti.png'";

						$htmlBody = '<td style="padding: 20px 0 30px 0;">
										<span style="font-size:14px;color:#555555">
											<p>
											Informamos que o seu acesso na plataforma da <a href="https://portal.espertibrasil.com.br">Esperti</a> foi bloqueado, pois não acusamos o pagamento da boleta referente à sua assinatura mensal do Buscador de Honorários. 
											<br> <br>
											Nesta oportunidade, convidamos a reativar sua assinatura acessando a plataforma da Esperti no ícone RECADASTRO e seguir as orientações da página.
											</p>

										</span>
									</td>';

						$html = email($url, $nomePerito, $htmlBody);

						$assunto = "Bloqueio de Acesso";
						$body = $html;
						$mail = Utility::SendMail($assunto, $body);

						$mail = emailAddres($mail,$objectJson);
						$mail->addCC("contato@espertibrasil.com.br");

						$mail->AddEmbeddedImage("https://portal.espertibrasil.com.br/images/ppEsperti.png", 'logo_esperti');
						try {
							if($mail->send()){
								$data["status"] = 200;
								$data["msg"] = "Email Enviado com Sucesso!";
							}else{
								$data["status"] = 201;
								$data["tipoErro"] = "Erro ao Enviar Email!";
								$data["msg"] = "Não foi possível enviar o e-mail de Alerta de Vencimento do Perito ".$nomePerito;
							}
						} catch (phpmailerException $e) {
							echo $e->errorMessage(); //Pretty error messages from PHPMailer
						} catch (Exception $e) {
							echo $e->getMessage(); //Boring error messages from anything else!
						}
					break;
					
					case 'remenberExtraction':
						$data = array();

						$inputJSON = file_get_contents('php://input');
						$objectJson = json_decode($inputJSON);

						$url = "'https://portal.espertibrasil.com.br/images/ppEsperti.png'";
						if($objectJson->haveTable){
							$auxText = '<b>Hoje possui extração nos seguintes itens abaixo:</b>
												<br>'.$objectJson->data;
						}else{
							$auxText = $objectJson->data;
						}


						$htmlBody = '<td style="padding: 20px 0 30px 0;">
										<span style="font-size:14px;color:#555555">
											<p>
												'.$auxText.'
											</p>
										</span>
									</td>';


						$html = emailAlert($url, $objectJson->tipoAlerta, $htmlBody);

						$assunto = "Lembrete de Extração";
						$body = $html;
						$mail = Utility::SendMail($assunto, $body);

						$mail = emailAddres($mail,$objectJson);
			
						$mail->AddEmbeddedImage("https://portal.espertibrasil.com.br/images/ppEsperti.png", 'logo_esperti');
						try {
							if($mail->send()){
								$data["status"] = 200;
								$data["msg"] = "Email Enviado com Sucesso!";
							}else{
								$data["status"] = 201;
								$data["tipoErro"] = "Erro ao Enviar Email!";
								$data["msg"] = "Não foi possível enviar o e-mail de Alerta de Vencimento do Perito ".$nomePerito;
							}
						} catch (phpmailerException $e) {
							echo $e->errorMessage(); //Pretty error messages from PHPMailer
						} catch (Exception $e) {
							echo $e->getMessage(); //Boring error messages from anything else!
						}
					break;

					case 'WebJurSendEmail':
						$data = array();

						$inputJSON = file_get_contents('php://input');
						$objectJson = json_decode($inputJSON);

						$url = "'https://portal.espertibrasil.com.br/images/ppEsperti.png'";

						$htmlBody = '<td style="padding: 20px 0 30px 0;">
										<span style="font-size:14px;color:#555555">
											<p>
												<b>Numero Processo: </b>'.$objectJson->processo.'
											</p>
											<p>
												<b>Descrição: </b>'.$objectJson->descricaoProcess.'
											</p>
										</span>
									</td>';


						$assunto = "WebJur: ".$objectJson->nomePerito;

						$html = emailAlert($url, $assunto, $htmlBody);
						$body = $html;
						$mail = Utility::SendMail($assunto, $body);

						$mail = emailAddres($mail,$objectJson);
						
						$mail->AddEmbeddedImage("https://portal.espertibrasil.com.br/images/ppEsperti.png", 'logo_esperti');
						try {
							if($mail->send()){
								$data["status"] = 200;
								$data["msg"] = "Email Enviado com Sucesso!";
							}else{
								$data["status"] = 201;
								$data["tipoErro"] = "Erro ao Enviar Email!";
								$data['body']= $body;
								$data["msg"] = "Não foi possível enviar o e-mail do WebJur ";
							}
						} catch (phpmailerException $e) {
							echo $e->errorMessage(); //Pretty error messages from PHPMailer
						} catch (Exception $e) {
							echo $e->getMessage(); //Boring error messages from anything else!
						}
					break;

					case 'error':
						$data = array();

						$inputJSON = file_get_contents('php://input');
						$objectJson = json_decode($inputJSON);

						$nomePeritoArray =explode(" ",  strtolower($objectJson->nome_perito));
						$nomePerito = ucfirst($nomePeritoArray[0]);
						$url = "'https://portal.espertibrasil.com.br/images/ppEsperti.png'";

						$textInfoAdicional = "";
						if(isset($objectJson->informacoes))
							$textInfoAdicional = '<b>Infomações Adicionais: </b>'.$objectJson->informacoes.'<br>';
						$htmlBody = '<td style="padding: 20px 0 30px 0;">
										<span style="font-size:14px;color:#555555">
											<p>
												<b>Hora do Erro: </b>'.date("d/m/Y H:i:s").'<br>
												<b>Pasta Localizada: </b>'.$objectJson->pasta.'<br>
												<b>Local: </b>'.$objectJson->local.'<br>
												<b>Tipo Erro: </b>'.$objectJson->tipoErro.'<br>
												<b>Mensagem: </b>'.$objectJson->msg.'<br>
												'.$textInfoAdicional.'
											</p>
										</span>
									</td>'; 

						$html = emailError($url, $objectJson->tipoErro, $htmlBody);

						$assunto = "Erro ".$objectJson->tipoErro;
						$body = $html;
						$mail = Utility::SendMail($assunto, $body);
	
						// $mail->addAddress("contato@espertibrasil.com.br");
						$mail = emailAddres($mail,$objectJson);

						$mail->addAddress("logerror@espertibrasil.com.br");
						$mail->addAddress("eduardoatenesilvamarinha@gmail.com");

						$mail->AddEmbeddedImage("https://portal.espertibrasil.com.br/images/ppEsperti.png", 'logo_esperti');

						try {
							if($mail->send()){
								$data["status"] = 200;
								$data["msg"] = "Email Enviado com Sucesso!";
							}else{
								$data["status"] = 201;
								$data["tipoErro"] = "Erro ao Enviar Email!";
								$data["msg"] = "Não foi possível enviar o e-mail de erro!";
							}
						} catch (phpmailerException $e) {
							echo $e->errorMessage(); //Pretty error messages from PHPMailer
						} catch (Exception $e) {
							echo $e->getMessage(); //Boring error messages from anything else!
						}
					break;
                }
                
			}
			else{
				$intReturn = -2;	
			}			
		}
	}
	elseif ($_SERVER['REQUEST_METHOD'] === "GET") {

		
	}
	
	switch ($intReturn) {
		case -2:
			response(401,
					"Unauthorized",
					null);
			break;
		case -1:
			response(400,
					"Invalid Request",
					null);
			break;	
		case 0:
			response(200,
					"Email Enviado com Sucesso",
					$data);
		break;
	}
	

	function emailAddres($mail,$objectJson){

		if(isset($objectJson->addAddress)){
			foreach ($objectJson->addAddress as $emailAdrress) {
				$mail->addAddress($emailAdrress);
			}
		}
		if(isset($objectJson->addAddressCopy)){
			foreach ($objectJson->addAddressCopy as $addAddressCopy) {
				$mail->addCC($addAddressCopy);
			}
		}
		if(isset($objectJson->addAddressOculto)){ //OCULTA FDAZER
			foreach ($objectJson->addAddressOculto as $addAddressOculto) {
				$mail->addBCC($addAddressOculto);
			}
		}
		return $mail;
	}

	function email($url, $nomePerito, $htmlBody){
		$email = '<body>
		<table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
			<tr style="height: 115px; background:linear-gradient(90deg, rgba(145,145,145,1) 0%, rgba(231,231,231,1) 50%, rgba(145,145,145,1) 100%);">
				<td align="center" style="padding: 80px 0 30px 0; 
					background-image: url('.$url.');
					background-size: auto 90%;
					background-repeat: no-repeat;
					background-position: center;">
				</td>
			</tr>
			<tr>
				<td bgcolor="#ffffff" style="padding: 40px 30px 40px 30px;">
					<table border="0" cellpadding="0" cellspacing="0" width="100%">
						<tr>
							<td style="text-align: center">
								<h2 style="color:#035A79">
									Olá, '.$nomePerito.'!
								</h2>
							</td>
						</tr>
						<tr>
							'.$htmlBody.'
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td bgcolor="#035978" style="padding: 10px 30px 10px 30px;">
					<table border="0" cellpadding="0" cellspacing="0" width="100%" style="color:white;">
						<tr>
							<td width="75%">
								<h3 style="">
									Abraços,
									<br>
									Equipe Esperti
								</h3>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</body>';

		return $email;
	}
	
	function emailAlert($url, $tipoAlerta, $htmlBody){
		$email = '<body>
		<table align="center" border="0" cellpadding="0" cellspacing="0" width="700">
			<tr style="height: 115px; background:linear-gradient(90deg, rgb(45,81,255,1) 0%, rgba(231,231,231,1) 50%, rgb(45,81,255,1) 100%);">
				<td align="center" style="padding: 80px 0 30px 0; 
					background-image: url('.$url.');
					background-size: auto 90%;
					background-repeat: no-repeat;
					background-position: center;">
				</td>
			</tr>
			<tr>
				<td bgcolor="#ffffff" style="padding: 40px 30px 40px 30px;">
					<table border="0" cellpadding="0" cellspacing="0" width="100%">
						<tr>
							<td style="text-align: left">
								<h2>
									<span style="color:#035A79">'.$tipoAlerta.' </span>
								</h2>
							</td>
						</tr>
						<tr>
							'.$htmlBody.'
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td bgcolor="#0000ff" style="padding: 10px 30px 10px 30px;">
					<table border="0" cellpadding="0" cellspacing="0" width="100%" style="color:white;">
						<tr>
							<td width="75%">
								<h3 style="">
									Abraços,
									<br>
									Equipe Esperti
								</h3>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</body>';

		return $email;
	}
	function emailError($url, $tipoErro, $htmlBody){
		$email = '<body>
		<table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
			<tr style="height: 115px; background:linear-gradient(90deg, rgb(249,93,93,1) 0%, rgba(231,231,231,1) 50%, rgb(249,93,93,1) 100%);">
				<td align="center" style="padding: 80px 0 30px 0; 
					background-image: url('.$url.');
					background-size: auto 90%;
					background-repeat: no-repeat;
					background-position: center;">
				</td>
			</tr>
			<tr>
				<td bgcolor="#ffffff" style="padding: 40px 30px 40px 30px;">
					<table border="0" cellpadding="0" cellspacing="0" width="100%">
						<tr>
							<td style="text-align: left">
								<h2>
									<span style="color:#035A79">'.$tipoErro.' </span>
								</h2>
							</td>
						</tr>
						<tr>
							'.$htmlBody.'
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td bgcolor="red" style="padding: 10px 30px 10px 30px;">
					<table border="0" cellpadding="0" cellspacing="0" width="100%" style="color:white;">
						<tr>
							<td width="75%">
								<h3 style="">
									Abraços,
									<br>
									Equipe Esperti
								</h3>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</body>';

		return $email;
	}

	/*
	* $objectJson->data_vencimento. Caso possuir boleto, o objectJson deverá vir com a propriedade de Data de Vencimento
	* $objectJson->hasAlteracoes.   Caso a proriedade $relatorioCompleto for falso, o objectJson deverá vir com a propriedade de hasAlteracoes.
	*
	*   relatorioCompleto = True. Deverá apresentar o texto do Relatório completo. Caso contrário, simboliza que o relatório deverá ser Reduzido.
	*  Em casos de Relatório Reduzido, o $objectJson deverá vir com a propriedade *hasAlteracoes*. Caso possuir alterações, irá apresentar o texto de alterações.
	*  Caso contrário, o texto que será informado é que não ocorreu nenhum alterações.
	*
	*	boleto			  = True. Deverá apresentar o texto referente a fatura. Caso contrário, o e-mail não deverá ser enviado o boleto 
	*
	*	Boleto Pendencias
	*	-   data_vencimento
	*	Relatorio Reduzido Pendencias
	*	-   hasAlteracoes
	*/
	function BodyTextEmailRelorio($objectJson,$relatorioCompleto, $boleto,$reenvioBoleto = false){
		$textBoleto = "";
		if($boleto){

			if($reenvioBoleto){
				$textBoleto = ' <p>
									Estamos reenviando sua fatura já está fechada, vence no dia <b style="color:black;">'.$objectJson->data_vencimento.'</b> e você pode conferir todos os detalhes no PDF anexo aqui no e-mail.
								</p>';
			}else{
				$textBoleto = ' <p>
									Sua fatura já está fechada, vence no dia <b style="color:black;">'.$objectJson->data_vencimento.'</b> e você pode conferir todos os detalhes no PDF anexo aqui no e-mail.
								</p>';		
			}
			$textBoleto = $textBoleto.'
							<p>
								Você pode realizar o pagamento em qualquer banco, na lotérica, nos canais digitais do seu banco (internet banking) ou app de pagamento.
							</p>
							<p>
								O processamento dos pagamentos pode levar até <b style="color:black;">2</b> dias úteis.
							<br>
							<p>
								O atraso no pagamento pode prejudicar a continuidade na prestação dos serviços.
							</p>';
		}

		$textRelatorio = "";
		if(!($reenvioBoleto)){
			if($relatorioCompleto){
				$textRelatorio = '	<p>
										Segue anexo o relatório completo de análise dos seus processos pelo <a href="https://portal.espertibrasil.com.br">Buscador de Honorários da Esperti</a>. Nele você poderá verificar as ordens de pagamento de honorários. O relatório auxilia na identificação dos processos com possível pendência no recebimento de honorários.
									</p>';
			}else{
				if($objectJson->hasAlteracoes){
					$textRelatorio = '	<p>
											Segue anexo o relatório dos processos que tiveram mudança de status no último mês. Nele você poderá acompanhar as ordens de pagamento de honorários expedidas no período. Nas colunas de busca de honorários, acompanhe processos que foram reclassificados. O programa entendeu que houve elevação de possibilidade de pendência de recebimentos.
										</p>';
				}else{
					$textRelatorio = '	<p>
											O <a href="https://portal.espertibrasil.com.br">Buscador de Honorários</a> está fazendo varreduras periódicas no seu acervo de processos procurando ordens de pagamento de honorários ou pendências de recebimentos. No último mês, não constatamos alteração nos status de pagamento e busca de honorários nos seus processos. Continuamos firmes neste monitoramento.
										</p>';
				}
			}
		}

		$htmlBody = '<td style="padding: 20px 0 30px 0;">
						<h3 style="color:#555555; text-align: justify;">
							'.$textRelatorio.'
							'.$textBoleto.'
						</h3>
					</td>';

		return $htmlBody;
	}
?>