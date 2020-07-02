import { Component, OnInit } from '@angular/core';
import { ResetService } from './reset.service';
import { ActivatedRoute } from '@angular/router';
import { FormGroup, Validators, FormBuilder } from '@angular/forms';

@Component({
  selector: 'app-reset',
  templateUrl: './reset.component.html',
  styleUrls: ['./reset.component.scss']
})
export class ResetComponent implements OnInit {

  formularioResetClave: FormGroup;
  mensajeNotificacion: string;
  mostrarNotificacion: boolean = true;
  token: string;
  flag: boolean = false;

  constructor(
    private formBuilder: FormBuilder,
    private resetService: ResetService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.formularioResetClave = this.formBuilder.group({
      clave: [
        '',
        Validators.compose([Validators.minLength(6), Validators.required]),
      ],
      claveConfirm: [
        '',
        Validators.compose([Validators.minLength(6), Validators.required]),
      ]
    }, { validator: this.checkPasswords });

    this.route.params.subscribe((response) => {
      this.token = response["token"];
      console.log(this.token);
      if (this.token != '') {
        this.resetService.verifyTokenReset(this.token).subscribe(
          (response) => {
            console.log(response);
            if (response.status == 1) {
              this.flag = true;
            }
          },
          (error) => {
            console.log(error);
          }
        )
      } else {
        console.log('No existe token');
      }
    });
  }

  ocultarNotificacion(value: boolean) {
    this.mostrarNotificacion = value;
  }

  validarClaveReset() {
    if (this.flag) {
      if (this.formularioResetClave.invalid) {
        this.formularioResetClave.get('clave').markAsDirty();
        this.formularioResetClave.get('claveConfirm').markAsDirty();
        return;
      }

      this.resetService.reset(
        this.formularioResetClave.get('clave').value,
        this.formularioResetClave.get('claveConfirm').value,
        this.token
      ).subscribe(
        (response) => {
          console.log(response);

        },
        (error) => {
          console.log(error);
        }
      );
    }
  }

  checkPasswords(group: FormGroup) {
    let pass = group.get('clave').value;
    let confirmPass = group.get('claveConfirm').value;

    return pass === confirmPass ? null : { notSame: true }
  }

}
